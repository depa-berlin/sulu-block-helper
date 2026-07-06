<?php

declare(strict_types=1);

namespace Depa\SuluBlockHelperBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Spam-protects email addresses with ROT13. The rendered markup only ever
 * contains the rot13'd address in the `mailto:` href, so scrapers cannot read
 * it; the shipped frontend module (Resources/js/website) reverses the ROT13 on
 * click for real users. Without that JS wired into the website build, the
 * generated links do not work — see the bundle README.
 */
class ObfuscateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('obfuscate', $this->parseObfuscate(...), ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('obfuscate', $this->obfuscate(...), ['is_safe' => ['html']]),
        ];
    }

    public function parseObfuscate(string $string): string
    {
        return \str_rot13($string);
    }

    public function obfuscate(string $mail, ?string $inlineText = null): string
    {
        $newMail = \str_rot13($mail);

        // Create a string with href and mailto.
        if (null !== $inlineText) {
            $link = \sprintf('<a href="mailto:%s">%s</a>', $newMail, $inlineText);
        } else {
            // Replace @ with [at] to prevent spambots from harvesting the address.
            $mail = \str_replace('@', '[at]', $mail);

            $link = \sprintf('<a href="mailto:%s">%s</a>', $newMail, $mail);
        }

        return \str_replace('<a', '<a data-obfuscate', $link);
    }
}
