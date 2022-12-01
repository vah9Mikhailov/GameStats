<?php
namespace App\Dto;

final class Parse
{

    /**
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $pageId;

    /**
     * @var int
     */
    private $revId;

    /**
     * @var array
     */
    private $text;

    /**
     * @var array
     */
    private $langLinks;

    /**
     * @var array
     */
    private $categories;

    /**
     * @var array
     */
    private $links;

    /**
     * @var array
     */
    private $templates;

    /**
     * @var array
     */
    private $images;

    /**
     * @var array
     */
    private $externalLinks;

    /**
     * @var array
     */
    private $sections;

    /**
     * @var array
     */
    private $parseWarnings;

    /**
     * @var string
     */
    private $displayTitle;

    /**
     * @var array
     */
    private $iwLinks;

    /**
     * @var array
     */
    private $properties;

    /**
     * @param string $title
     * @param int $pageId
     * @param int $revId
     * @param array $text
     * @param array $langLinks
     * @param array $categories
     * @param array $links
     * @param array $templates
     * @param array $images
     * @param array $externalLinks
     * @param array $sections
     * @param array $parseWarnings
     * @param string $displayTitle
     * @param array $iwLinks
     * @param array $properties
     */
    public function __construct(
        string $title,
        int    $pageId,
        int    $revId,
        array  $text,
        array  $langLinks,
        array  $categories,
        array $links,
        array $templates,
        array $images,
        array $externalLinks,
        array $sections,
        array $parseWarnings,
        string $displayTitle,
        array $iwLinks,
        array $properties
    )
    {
        $this->title = $title;
        $this->pageId = $pageId;
        $this->revId = $revId;
        $this->text = $text;
        $this->langLinks = $langLinks;
        $this->categories = $categories;
        $this->links = $links;
        $this->templates = $templates;
        $this->images = $images;
        $this->externalLinks = $externalLinks;
        $this->sections = $sections;
        $this->parseWarnings = $parseWarnings;
        $this->displayTitle = $displayTitle;
        $this->iwLinks = $iwLinks;
        $this->properties = $properties;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getIwLinks(): array
    {
        return $this->iwLinks;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getPageId(): int
    {
        return $this->pageId;
    }

    /**
     * @return int
     */
    public function getRevId(): int
    {
        return $this->revId;
    }

    /**
     * @return array
     */
    public function getText(): array
    {
        return $this->text;
    }

    /**
     * @return array
     */
    public function getLangLinks(): array
    {
        return $this->langLinks;
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @return array
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @return array
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @return array
     */
    public function getExternalLinks(): array
    {
        return $this->externalLinks;
    }

    /**
     * @return array
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * @return array
     */
    public function getParseWarnings(): array
    {
        return $this->parseWarnings;
    }

    /**
     * @return string
     */
    public function getDisplayTitle(): string
    {
        return $this->displayTitle;
    }

}
