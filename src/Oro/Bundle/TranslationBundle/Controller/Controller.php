<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\TemplateReferenceInterface;

class Controller
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var string|TemplateReferenceInterface
     */
    protected $template;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param Translator $translator
     * @param EngineInterface $templating
     * @param string|TemplateReferenceInterface $template a template name or a TemplateReferenceInterface instance
     * @param array $options array('domains' => array(), 'debug' => true|false)
     * @throws \InvalidArgumentException
     */
    public function __construct(Translator $translator, EngineInterface $templating, $template, $options)
    {
        $this->translator = $translator;
        $this->templating = $templating;
        if (empty($template) || !($template instanceof TemplateReferenceInterface || is_string($template))) {
            throw new \InvalidArgumentException('Please provide valid twig template as third argument');
        }
        $this->template = $template;
        $this->options = $options;
    }

    /**
     * Action point for js translation resource
     *
     * @param Request $request
     * @param string $_locale
     * @return Response
     */
    public function indexAction(Request $request, $_locale)
    {
        $domains = isset($this->options['domains']) ? $this->options['domains'] : array();
        $debug = isset($this->options['debug']) ? (bool)$this->options['debug'] : false;

        $content = $this->renderJsTranslationContent($domains, $_locale, $debug);

        return new Response($content, 200, array('Content-Type' => $request->getMimeType('js')));
    }

    /**
     * Combines JSON with js translation and renders js-resource
     *
     * @param array $domains
     * @param string $locale
     * @param bool $debug
     * @return string
     */
    public function renderJsTranslationContent(array $domains, $locale, $debug = false)
    {
        $domainsTranslations = $this->translator->getTranslations($domains, $locale);

        $result = [
            'locale' => $locale,
            'defaultDomain' => reset($domains) ?: '',
            'translations' => [$locale => []]
        ];
        if ($debug) {
            $result['debug'] = true;
        }

        foreach ($domainsTranslations as $domain => $translations) {
            $result['translations'][$locale][$domain] = $translations;
        }

        return $this->templating->render($this->template, array('json' => $result));
    }
}
