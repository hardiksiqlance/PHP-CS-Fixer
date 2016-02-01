<?php

/*
 * This file is part of the PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\Symfony;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class NativeFunctionCasingFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, Tokens $tokens)
    {
        static $nativeFunctionNames = null;

        if (null === $nativeFunctionNames) {
            $nativeFunctionNames = $this->getNativeFunctionNames();
        }

        for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
            // test if we are at a function all
            if (!$tokens[$index]->isGivenKind(T_STRING)) {
                continue;
            }

            $next = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$next]->equals('(')) {
                $index = $next;
                continue;
            }

            $functionNamePrefix = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$functionNamePrefix]->isGivenKind(array(T_DOUBLE_COLON, T_NEW, T_OBJECT_OPERATOR, T_FUNCTION))) {
                continue;
            }

            // do not though the function call if it is to a function in a namespace other than the default
            if (
                $tokens[$functionNamePrefix]->isGivenKind(T_NS_SEPARATOR)
                && $tokens[$tokens->getPrevMeaningfulToken($functionNamePrefix)]->isGivenKind(T_STRING)
            ) {
                continue;
            }

            // test if the function call is to a native PHP function
            $lower = strtolower($tokens[$index]->getContent());
            if (!array_key_exists($lower, $nativeFunctionNames)) {
                continue;
            }

            $tokens[$index]->setContent($nativeFunctionNames[$lower]);

            $index = $next;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Function defined by PHP should be called using the correct casing.';
    }

    private function getNativeFunctionNames()
    {
        $allFunctions = get_defined_functions();
        $functions = array();
        foreach ($allFunctions['internal'] as $function) {
            $functions[strtolower($function)] = $function;
        }

        return $functions;
    }
}
