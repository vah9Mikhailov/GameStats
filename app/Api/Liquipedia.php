<?php
namespace App\Api;

use App\Dto\Parse;

class Liquipedia
{

    /**
     * @var Transport
     */
    private $transport;

    /**
     * @var string
     */
    private $header = 'User-Agent: TestBot/1.0';

    /**
     * @param Transport $transport
     */
    public function __construct(Transport $transport)
    {
        $this->transport = $transport;
    }


    /**
     * @param string $page
     * @return string
     */
    public function getPageRequest(string $page): string
    {

        $params = [
            'action' => "parse",
            'format' => "json",
            'page'=> $page
        ];

        $content = $this->transport->getContents(http_build_query($params),$this->header);
        $result = json_decode($content, true);
        $parse = $result['parse'];
        $parseDto = new Parse(
            (string)$parse['title'] ?? "",
            (int)$parse['pageid'] ?? "",
            (int)$parse['revid'] ?? "",
            (array)$parse['text'] ?? [],
            (array)$parse['langlinks'] ?? [],
            (array)$parse['categories'] ?? [],
            (array)$parse['links'] ?? [],
            (array)$parse['templates'] ?? [],
            (array)$parse['images'] ?? [],
            (array)$parse['externallinks']  ?? [],
            (array)$parse['sections'] ?? [],
            (array)$parse['parsewarnings'] ?? [],
            (string)$parse['displaytitle'] ?? "",
            (array)$parse['iwlinks'] ?? [],
            (array)$parse['properties'] ?? [],
        );
        if (!empty($parseDto->getText())) {
            if (!empty($parseDto->getText()['*'])) {
                return $parseDto->getText()['*'];
            } else {
                throw new \DomainException("Ключа '*' не существует");
            }
        } else {
            throw new \DomainException("Ключа 'Text' не существует");
        }

    }



}
