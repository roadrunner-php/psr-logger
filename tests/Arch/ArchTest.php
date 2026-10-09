<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Arch;

use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;

#[CoversNothing]
#[Test]
final class ArchTest
{
    private const FORBIDDEN_FUNCTIONS = [
        'dd', 'exit', 'die', 'var_dump', 'echo', 'print', 'trap', 'dump', 'tr', 'td', 'error_log',
    ];

    /** Tokens that may precede a name without making it a function call (methods, declarations, constants). */
    private const NON_CALL_PREFIXES = [\T_OBJECT_OPERATOR, \T_NULLSAFE_OBJECT_OPERATOR, \T_DOUBLE_COLON, \T_FUNCTION, \T_NEW, \T_CONST];

    public function testForgottenDebugFunctions(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(\dirname(__DIR__, 2) . '/src', \FilesystemIterator::SKIP_DOTS),
        );

        $violations = [];
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            foreach ($this->usedFunctions($file->getPathname()) as $function) {
                \in_array($function, self::FORBIDDEN_FUNCTIONS, true) and $violations[] = \sprintf(
                    'Function `%s()` is used in %s.',
                    $function,
                    $file->getPathname(),
                );
            }
        }

        Assert::same($violations, [], \implode("\n", $violations));
    }

    /**
     * @return list<non-empty-string> Lower-cased names of called functions and language constructs.
     */
    private function usedFunctions(string $path): array
    {
        $tokens = \array_values(\array_filter(
            \PhpToken::tokenize((string) \file_get_contents($path)),
            static fn(\PhpToken $token): bool => !$token->isIgnorable(),
        ));

        $result = [];
        foreach ($tokens as $i => $token) {
            if ($token->is([\T_EXIT, \T_ECHO, \T_PRINT])) {
                $result[] = \strtolower($token->text);
                continue;
            }

            if (!$token->is([\T_STRING, \T_NAME_FULLY_QUALIFIED])
                || ($tokens[$i + 1] ?? null)?->text !== '('
                || ($i > 0 && $tokens[$i - 1]->is(self::NON_CALL_PREFIXES))
            ) {
                continue;
            }

            $result[] = \strtolower(\ltrim($token->text, '\\'));
        }

        return $result;
    }
}
