<?php

namespace Lexio\AdminBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\TranslatableListener;

trait TranslatableHints
{
    public function addTranslatableHints(QueryBuilder $builder, string $locale = 'en'): Query
    {
        $query = $builder->getQuery();

        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            \Gedmo\Translatable\Query\TreeWalker\TranslationWalker::class
        );

        $query->setHint(
            TranslatableListener::HINT_TRANSLATABLE_LOCALE,
            $locale
        );

        $query->setHint(
            TranslatableListener::HINT_FALLBACK,
            1
        );

        return $query;
    }
}
