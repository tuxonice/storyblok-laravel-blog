<?php

namespace App\Storyblok\Transfers;

use Tlab\StoryblokTransfers\Transfers\AssetTransfer;
use Tlab\StoryblokTransfers\Transfers\RichtextTransfer;
use Tlab\TransferObjects\AbstractTransfer;

/**
 * !!! THIS TRANSFER CLASS FILE IS AUTO-GENERATED, CHANGES WILL BREAK YOUR PROJECT
 * !!! DO NOT CHANGE ANYTHING IN THIS FILE
 */
class PostTransfer extends AbstractTransfer
{
    /**
     * @var string|null
     */
    private ?string $title = null;

    /**
     * @var string|null
     */
    private ?string $excerpt = null;

    /**
     * @var AssetTransfer|null
     */
    private ?AssetTransfer $coverImage = null;

    /**
     * @var string|null
     */
    private ?string $publishedAt = null;

    /**
     * @var string|null
     */
    private ?string $author = null;

    /**
     * @var array<string>
     */
    private array $categories = [];

    /**
     * @var array<string>
     */
    private array $tags = [];

    /**
     * @var RichtextTransfer|null
     */
    private ?RichtextTransfer $body = null;

    /**
     * @var string|null
     */
    private ?string $seoTitle = null;

    /**
     * @var string|null
     */
    private ?string $seoDescription = null;

    /**
     * @var AssetTransfer|null
     */
    private ?AssetTransfer $ogImage = null;

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     *
     * @return $this
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    /**
     * @param string|null $excerpt
     *
     * @return $this
     */
    public function setExcerpt(?string $excerpt): self
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    /**
     * @return AssetTransfer|null
     */
    public function getCoverImage(): ?AssetTransfer
    {
        return $this->coverImage;
    }

    /**
     * @param AssetTransfer|null $coverImage
     *
     * @return $this
     */
    public function setCoverImage(?AssetTransfer $coverImage): self
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPublishedAt(): ?string
    {
        return $this->publishedAt;
    }

    /**
     * @param string|null $publishedAt
     *
     * @return $this
     */
    public function setPublishedAt(?string $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAuthor(): ?string
    {
        return $this->author;
    }

    /**
     * @param string|null $author
     *
     * @return $this
     */
    public function setAuthor(?string $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param array<string> $categories
     *
     * @return $this
     */
    public function setCategories(array $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @param string $category
     *
     * @return $this
     */
    public function addCategory(string $category): self
    {
        $this->categories[] = $category;

        return $this;
    }
    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param array<string> $tags
     *
     * @return $this
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @param string $tag
     *
     * @return $this
     */
    public function addTag(string $tag): self
    {
        $this->tags[] = $tag;

        return $this;
    }
    /**
     * @return RichtextTransfer|null
     */
    public function getBody(): ?RichtextTransfer
    {
        return $this->body;
    }

    /**
     * @param RichtextTransfer|null $body
     *
     * @return $this
     */
    public function setBody(?RichtextTransfer $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    /**
     * @param string|null $seoTitle
     *
     * @return $this
     */
    public function setSeoTitle(?string $seoTitle): self
    {
        $this->seoTitle = $seoTitle;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    /**
     * @param string|null $seoDescription
     *
     * @return $this
     */
    public function setSeoDescription(?string $seoDescription): self
    {
        $this->seoDescription = $seoDescription;

        return $this;
    }

    /**
     * @return AssetTransfer|null
     */
    public function getOgImage(): ?AssetTransfer
    {
        return $this->ogImage;
    }

    /**
     * @param AssetTransfer|null $ogImage
     *
     * @return $this
     */
    public function setOgImage(?AssetTransfer $ogImage): self
    {
        $this->ogImage = $ogImage;

        return $this;
    }

}
