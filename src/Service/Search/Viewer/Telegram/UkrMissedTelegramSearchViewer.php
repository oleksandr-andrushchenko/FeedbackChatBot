<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrMissed\UkrMissedDisappearedPersons;
use App\Entity\Search\UkrMissed\UkrMissedPerson;
use App\Entity\Search\UkrMissed\UkrMissedWantedPersons;
use App\Service\Intl\TimeProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class UkrMissedTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('ukr_missed'), $modifier);
    }

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;

        return match (get_class($record)) {
            UkrMissedDisappearedPersons::class => $this->getDisappearedPersonsMessage($record, $searchTerm, $full),
            UkrMissedWantedPersons::class => $this->getWantedPersonsMessage($record, $searchTerm, $full),
        };
    }

    private function getDisappearedPersonsMessage(UkrMissedDisappearedPersons $record, FeedbackSearchTerm $searchTerm, bool $full): string
    {
        $message = '😐 ';
        $message .= $this->implodeResult(
            $this->trans('disappeared_persons_title'),
            $record->getItems(),
            $this->getWrapMessageCallback($searchTerm, $full),
            $full
        );

        return $message;
    }

    private function getWantedPersonsMessage(UkrMissedWantedPersons $record, FeedbackSearchTerm $searchTerm, bool $full): string
    {
        $message = '🚨 ';
        $message .= $this->implodeResult(
            $this->trans('wanted_persons_title'),
            $record->getItems(),
            $this->getWrapMessageCallback($searchTerm, $full),
            $full
        );

        return $message;
    }

    public function getWrapMessageCallback(FeedbackSearchTerm $searchTerm, bool $full): callable
    {
        $m = $this->modifier;

        $term = $searchTerm->getNormalizedText();

        return fn (UkrMissedPerson $item): array => [
            $m->create()
                ->add($m->filterModifier())
                ->add($m->implodeModifier(' '))
                ->add($m->trimModifier())
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                ->add($m->slashesModifier())
                ->add($m->boldModifier())
                ->add($m->bracketsModifier($this->trans('name')))
                ->apply([$item->getSurname(), $item->getName(), $item->getMiddleName()]),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('gender')))
                ->apply($item->getSex()),
            $m->create()
                ->add($m->datetimeModifier(TimeProvider::DATE))
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('born_at')))
                ->apply($item->getBirthday()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('precaution')))
                ->apply($item->getPrecaution()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add(
                    $m->bracketsModifier(
                        $m->create()
                            ->add($m->filterModifier())
                            ->add($m->implodeModifier('; '))
                            ->add($m->trimModifier())
                            ->add($m->emptyNullModifier())
                            ->add($m->slashesModifier())
                            ->apply($item->getArticles())
                    )
                )
                ->add($m->prependModifier(' '))
                ->add($m->prependModifier($m->redWhiteModifier()($item->getDisappeared() === false)))
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->bracketsModifier($this->trans('category')))
                ->add($m->slashesModifier())
                ->apply($item->getCategory()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('address')))
                ->apply($item->getAddress()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('organ')))
                ->apply($item->getOrgan()),
            $m->create()
                ->add($m->datetimeModifier(TimeProvider::DATE))
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('absent_at')))
                ->apply($item->getDate()),
        ];
    }
}
