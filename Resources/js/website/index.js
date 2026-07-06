// Website (frontend) entry for the depa/sulu-block-* bundles.
// Import this once in your project's website asset build, e.g.:
//   import '../../vendor/depa/sulu-block-helper/Resources/js/website';
//
// It powers the `obfuscate` Twig function (Depa\SuluBlockHelperBundle\Twig\
// ObfuscateExtension): the markup ships the email ROT13-encoded inside the
// mailto href, and this reverses it on click so only real users reach the
// real address.

export function rot13(string) {
    return string.replace(/[a-zA-Z]/g, (c) =>
        String.fromCharCode((c <= 'Z' ? 90 : 122) >= (c = c.charCodeAt(0) + 13) ? c : c - 26)
    );
}

export function obfuscateEmails() {
    document.querySelectorAll('a[data-obfuscate]').forEach((link) => {
        link.addEventListener('click', function (e) {
            const encoded = this.href.split('mailto:')[1];
            e.preventDefault();
            window.location.href = `mailto:${rot13(encoded)}`;
        });
    });
}

if (typeof document !== 'undefined') {
    if (document.readyState !== 'loading') {
        obfuscateEmails();
    } else {
        document.addEventListener('DOMContentLoaded', obfuscateEmails);
    }
}
