<?php

class TemplateManager
{

    const QUOTE_DESTINATION_LINK = '[quote:destination_link]';
    const QUOTE_SUMMARY_HTML = '[quote:summary_html]';
    const QUOTE_SUMMARY = '[quote:summary]';
    const QUOTE_DESTINATION_NAME = '[quote:destination_name]';
    const USER_FIRST_NAME = '[user:first_name]';

    public function getTemplateComputed(Template $tpl, array $data)
    {
        if (!$tpl) {
            throw new \RuntimeException('no tpl given');
        }

        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $data);
        $replaced->content = $this->computeText($replaced->content, $data);

        return $replaced;
    }

    private function computeText($text, array $data)
    {
        $APPLICATION_CONTEXT = ApplicationContext::getInstance();

        $quote = $this->getQuote($data);

        if ($quote)
        {
            $_quoteFromRepository = QuoteRepository::getInstance()->getById($quote->id);
            $usefulObject = SiteRepository::getInstance()->getById($quote->siteId);
            $destinationOfQuote = DestinationRepository::getInstance()->getById($quote->destinationId);

            if(strpos($text, self::QUOTE_DESTINATION_LINK) !== false){
                $destination = DestinationRepository::getInstance()->getById($quote->destinationId);
            }

            $containsSummaryHtml = strpos($text, self::QUOTE_SUMMARY_HTML);
            $containsSummary     = strpos($text, self::QUOTE_SUMMARY);

            if ($containsSummaryHtml !== false || $containsSummary !== false) {
                if ($containsSummaryHtml !== false) {
                    $text = str_replace(
                        self::QUOTE_SUMMARY_HTML,
                        Quote::renderHtml($_quoteFromRepository),
                        $text
                    );
                }
                if ($containsSummary !== false) {
                    $text = str_replace(
                        self::QUOTE_SUMMARY,
                        Quote::renderText($_quoteFromRepository),
                        $text
                    );
                }
            }

            (strpos($text, self::QUOTE_DESTINATION_NAME) !== false) and $text = str_replace(self::QUOTE_DESTINATION_NAME,$destinationOfQuote->countryName,$text);
        }

        if (isset($destination))
            $text = str_replace(self::QUOTE_DESTINATION_LINK, $usefulObject->url . '/' . $destination->countryName . '/quote/' . $_quoteFromRepository->id, $text);
        else
            $text = str_replace(self::QUOTE_DESTINATION_LINK, '', $text);

        /*
         * USER
         * [user:*]
         */
        $_user  = $this->getUserData($data,$APPLICATION_CONTEXT);
        if($_user) {
            (strpos($text, self::USER_FIRST_NAME) !== false) and $text = str_replace(self::USER_FIRST_NAME, ucfirst(mb_strtolower($_user->firstname)), $text);
        }

        return $text;
    }

    private function getQuote(array $data){
        return (isset($data['quote']) and $data['quote'] instanceof Quote) ? $data['quote'] : null;
    }

    private function getUserData(array $data,$applicationContext)    {
        return (isset($data['user'])  and ($data['user']  instanceof User))  ? $data['user']  : $applicationContext->getCurrentUser();
    }
}
