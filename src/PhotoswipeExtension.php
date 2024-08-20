<?php

namespace Matfire\CommonmarkPhotoswipe;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class PhotoswipeExtension implements ExtensionInterface, NodeRendererInterface
{
    protected Environment $environment;

    protected array $rendered = [];

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(Image::class, $this, 100);
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        /** @var Image $node */
        $image = new HtmlElement('img', ['src' => $node->getUrl(), 'alt' => $node->getTitle() ?? '']);
        $size = getimagesize($node->getUrl());
        return new HtmlElement('a', ['href' => $node->getUrl(), 'target' => '_blank', 'data-pswp-width' => strval($size[0]), 'data-pswp-height' => strval($size[1])], $image);
    }
}