<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace Wirecard\ElasticEngine\Model;

class TranslationFallback extends \Magento\Framework\Phrase\Renderer\Translate
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
     * Wrapper function to intercept translation from Core Magento2 functionality.
     * This method gets a translation and if there is no entry in the set locale, it gets the translation from the
     * defined fallback locale.
     *
     * @param \Magento\Framework\Phrase\Renderer\Translate $subject
     * @param callable $proceed
     * @param array $source
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public function aroundRender(\Magento\Framework\Phrase\Renderer\Translate $subject, callable $proceed, array $source, array $arguments)
    {
        if (!$this->fallbackTranslations) {
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
