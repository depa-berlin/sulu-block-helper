<?php

declare(strict_types=1);

namespace Depa\SuluBlockHelperBundle\Twig;

use Symfony\Component\Intl\Countries;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Resolves an ISO 3166-1 alpha-2 country code to its localised country name.
 * Used by block--content-account-address to render an account's country.
 */
class CountryTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('country', $this->getCountryByCode(...)),
        ];
    }

    public function getCountryByCode(?string $countryCode): string
    {
        if (!$countryCode) {
            return '';
        }

        // Unknown codes must not blow up rendering — fall back to the raw code.
        if (!Countries::exists($countryCode)) {
            return $countryCode;
        }

        return Countries::getName($countryCode);
    }
}
