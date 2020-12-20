<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\ClassificationBundle\Command;

use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\ClassificationBundle\Model\CollectionManagerInterface;
use Sonata\ClassificationBundle\Model\ContextInterface;
use Sonata\ClassificationBundle\Model\ContextManagerInterface;
use Sonata\ClassificationBundle\Model\TagManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixContextCommand extends Command
{
    private $categoryManager;
    private $tagManager;
    private $collectionManager;
    private $contextManager;

    public function __construct(CategoryManagerInterface $categoryManager,
                                TagManagerInterface  $tagManager,
                                CollectionManagerInterface $collectionManager,
                                ContextManagerInterface $contextManager)
    {
        $this->categoryManager = $categoryManager;
        $this->tagManager = $tagManager;
        $this->collectionManager = $collectionManager;
        $this->contextManager = $contextManager;
    }


    public function configure(): void
    {
        $this->setName('sonata:classification:fix-context');
        $this->setDescription('Generate the default context if none defined and attach the context to all elements');
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $contextManager = $this->contextManager;
        $tagManager = $this->tagManager;
        $collectionManager = $this->collectionManager;
        $categoryManager = $this->categoryManager;

        $output->writeln('1. Checking default context');

        $defaultContext = $contextManager->findOneBy([
            'id' => ContextInterface::DEFAULT_CONTEXT,
        ]);

        if (!$defaultContext) {
            $output->writeln(' > default context is missing, creating one');
            $defaultContext = $contextManager->create();
            $defaultContext->setId(ContextInterface::DEFAULT_CONTEXT);
            $defaultContext->setName('Default');
            $defaultContext->setEnabled(true);

            $contextManager->save($defaultContext);
        } else {
            $output->writeln(' > default context exists');
        }

        $output->writeln('2. Find tag without default context');

        foreach ($tagManager->findBy([]) as $tag) {
            if ($tag->getContext()) {
                continue;
            }

            $output->writeln(sprintf(' > attach default context to tag: %s (%s)', $tag->getSlug(), $tag->getId()));
            $tag->setContext($defaultContext);

            $tagManager->save($tag);
        }

        $output->writeln('3. Find collection without default context');

        foreach ($collectionManager->findBy([]) as $collection) {
            if ($collection->getContext()) {
                continue;
            }

            $output->writeln(sprintf(' > attach default context to collection: %s (%s)', $collection->getSlug(), $collection->getId()));
            $collection->setContext($defaultContext);

            $collectionManager->save($collection);
        }

        $output->writeln('3. Find category without default context');

        foreach ($categoryManager->findBy([]) as $category) {
            if ($category->getContext()) {
                continue;
            }

            $output->writeln(sprintf(' > attach default context to collection: %s (%s)', $category->getSlug(), $category->getId()));
            $category->setContext($defaultContext);

            $categoryManager->save($category);
        }

        $output->writeln('Done!');
    }
}
