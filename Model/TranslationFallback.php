<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Model;

use Magento\Framework\Phrase\Renderer\Translate;

class TranslationFallback extends Translate
{
    /**
     * @var string
     */
    private $fallbackLocale = "en_US";

    /**
     * @var array
     */
    private $fallbackTranslations = null;

    /**
     * @var bool
     */
    private $fallbackTranslationsLoading;

    /**
     * Wrapper function to intercept translation from Core Magento2 functionality.
     * This method gets a translation and if there is no entry in the set locale, it gets the translation from the
     * defined fallback locale.
     *
     * @param Translate $subject
     * @param callable $proceed
     * @param array $source
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public function aroundRender(Translate $subject, callable $proceed, array $source, array $arguments)
    {
        if (!$this->fallbackTranslations && !$this->fallbackTranslationsLoading) {
            $this->fallbackTranslationsLoading = true;

            try {
                $originalLocale = $subject->translator->getLocale();
                $subject->translator->setLocale($this->fallbackLocale);
                $subject->translator->loadData(null, true);

                try {
                    $this->fallbackTranslations = $subject->translator->getData();
                } catch (\Exception $e) {
                    $subject->logger->critical($e->getMessage());
                    throw $e;
                }
                $subject->translator->setLocale($originalLocale);
                $subject->translator->loadData(null, true);
            } finally {
                $this->fallbackTranslationsLoading = false;
            }
        }

        $translationKey = end($source);
        $translationKey = str_replace('\"', '"', $translationKey);
        $translationKey = str_replace("\\'", "'", $translationKey);

        // Call the wrapped render function
        $result = $proceed($source, $arguments);

        // Check if fallback locale lookup is necessary
        if ($result == $translationKey && array_key_exists($translationKey, $this->fallbackTranslations)) {
            return $this->fallbackTranslations[$translationKey];
        }
        return $result;
    }
}
