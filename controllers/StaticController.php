<?php
/** Public, no-login pages linked from the home page footer. */
class StaticController {

    public function privacyAction(): void {
        $pageTitle = 'Privacy Policy';
        $content   = 'privacy';
        include __DIR__ . '/../views/static/page.php';
    }

    public function termsAction(): void {
        $pageTitle = 'Terms of Service';
        $content   = 'terms';
        include __DIR__ . '/../views/static/page.php';
    }

    public function supportAction(): void {
        $pageTitle = 'App Support';
        $content   = 'support';
        include __DIR__ . '/../views/static/page.php';
    }

    public function deletionAction(): void {
        $pageTitle = 'Data Deletion Request';
        $content   = 'deletion';
        include __DIR__ . '/../views/static/page.php';
    }
}
