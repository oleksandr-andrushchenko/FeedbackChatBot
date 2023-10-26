<?php

declare(strict_types=1);

namespace App\Tests\Functional\Feedback\Telegram\Bot;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Feedback\Telegram\Bot\SearchFeedbackTelegramBotConversationState;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Service\Feedback\Telegram\Bot\Conversation\SearchFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Fixtures;
use App\Tests\Functional\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;
use Generator;

class SearchFeedbackTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    /**
     * @param string $input
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(string $input): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this
            ->type($input)
            ->shouldSeeStateStep(
                $this->getConversation(),
                SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED
            )
            ->shouldSeeReply(
                'query.search_term',
            )
            ->shouldSeeButtons(
                $this->helpButton(),
                $this->cancelButton(),
            )
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'button' => [
            'input' => $this->command('search'),
        ];

        yield 'input' => [
            'input' => FeedbackTelegramBotGroup::SEARCH,
        ];
    }

    /**
     * @param SearchTermTransfer|null $searchTerm
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider searchTermStepDataProvider
     */
    public function testSearchTermStep(
        ?SearchTermTransfer $searchTerm,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerm,
            SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep
        );
    }

    public function searchTermStepDataProvider(): Generator
    {
        $validators = [
            ['empty', '', 'text.not_blank'],
            ['multiple lines', "фывasdqwe йцуйґ\r\nd", 'text.single_line'],
            ['forbidden chars', 'qwasdйц(уeqwe) sdf', 'text.allowed_chars'],
            ['too short', 'і', 'text.min_length'],
            ['too long', str_repeat('і', 256 + 1), 'text.max_length'],
        ];

        foreach ($validators as [$key, $input, $reply]) {
            yield 'type ' . $key => [
                'searchTerms' => null,
                'input' => $input,
                'shouldSeeReplies' => [
                    $reply,
                    'query.search_term',
                ],
                'shouldSeeButtons' => [
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
                'shouldSeeFirstSearchTermText' => null,
            ];
        }

        yield 'type & unknown type' => [
            'searchTerm' => null,
            'input' => $input = 'any_search_term',
            'shouldSeeReplies' => [
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                $this->searchTermTypeButton(SearchTermType::unknown),
                $this->removeButton($input),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'type & known type' => [
            'searchTerm' => null,
            'input' => Fixtures::getMessengerUserProfileUrl(new MessengerUserTransfer(Messenger::telegram, 'id', 'any_search_term')),
            'shouldSeeReplies' => [
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'remove' => [
            'searchTerm' => $searchTerm = new SearchTermTransfer('any_search_term', SearchTermType::onlyfans_username),
            'input' => $this->removeButton($searchTerm->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'next' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', SearchTermType::person_name),
            'input' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),

            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'help & empty search term' => [
            'searchTerm' => null,
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term',
                'search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'help & non-empty search term' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', SearchTermType::person_name),
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term',
                'search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'cancel & empty search term' => [
            'searchTerm' => null,
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];
    }

    /**
     * @param SearchTermTransfer $searchTerm
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider searchTermTypeStepDataProvider
     */
    public function testSearchTermTypeStep(
        SearchTermTransfer $searchTerm,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerm,
            SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep
        );
    }

    public function searchTermTypeStepDataProvider(): Generator
    {
        yield 'type wrong' => [
            'searchTerm' => $searchTerm = (new SearchTermTransfer('any_search_term'))
                ->setTypes([
                    SearchTermType::instagram_username,
                    SearchTermType::telegram_username,
                    SearchTermType::organization_name,
                ]),
            'input' => 'unknown',
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'type not in the list' => [
            'searchTerm' => $searchTerm = (new SearchTermTransfer('any_search_term'))
                ->setTypes([
                    SearchTermType::instagram_username,
                    SearchTermType::telegram_username,
                    SearchTermType::organization_name,
                ]),
            'input' => $this->searchTermTypeButton(SearchTermType::tiktok_username),
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'select type' => [
            'searchTerm' => $searchTerm = (new SearchTermTransfer('any_search_term'))
                ->setTypes([
                    SearchTermType::instagram_username,
                ]),
            'input' => $this->searchTermTypeButton($searchTerm->getTypes()[0]),
            'shouldSeeReplies' => [
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'remove' => [
            'searchTerm' => $searchTerm = (new SearchTermTransfer('any_search_term'))
                ->setTypes([
                    SearchTermType::instagram_username,
                ]),
            'input' => $this->removeButton($searchTerm->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'help' => [
            'searchTerm' => $searchTerm = (new SearchTermTransfer('any_search_term'))
                ->setTypes([
                    SearchTermType::instagram_username,
                    SearchTermType::telegram_username,
                    SearchTermType::organization_name,
                ]),
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term_type',
                'search_term_type',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => new SearchTermTransfer('any_search_term'),
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];
    }


    /**
     * @param SearchTermTransfer $searchTerm
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider confirmStepDataProvider
     */
    public function testConfirmStep(
        SearchTermTransfer $searchTerm,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            FeedbackSearchTerm::class,
            Feedback::class,
        ]);

        $state = (new SearchFeedbackTelegramBotConversationState())
            ->setSearchTerm($searchTerm)
            ->setStep(SearchFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED)
        ;

        $conversation = $this->createConversation(SearchFeedbackTelegramBotConversation::class, $state);

        $this
            ->type($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function confirmStepDataProvider(): Generator
    {
        yield 'type wrong' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            'input' => 'unknown',
            'shouldSeeReplies' => [
                'reply.wrong',
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'yes & empty results' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            'input' => $this->yesButton(),
            'shouldSeeReplies' => [
                'reply.empty_list',
                'query.create_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_CREATE_CONFIRM_QUERIED,
        ];

        yield 'yes & non-empty results' => [
            'searchTerm' => $searchTerm = new SearchTermTransfer(Fixtures::INSTAGRAM_USERNAME_3, SearchTermType::instagram_username),
            'input' => $this->yesButton(),
            'shouldSeeReplies' => [
                'reply.title',
                $searchTerm->getText(),
                $this->searchTermTypeTrans($searchTerm->getType()),
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'prev' => [
            'searchTerm' => $searchTerm = new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'help' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.confirm',
                'confirm',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SearchFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', SearchTermType::unknown),
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                'reply.canceled',
                'query.action',
            ],
            'shouldSeeButtons' => [
                $this->commandButton('create'),
                $this->commandButton('search'),
                $this->commandButton('lookup'),
            ],
            'shouldSeeStep' => null,
        ];
    }

    protected function test(
        ?SearchTermTransfer $searchTerm,
        ?int $stateStep,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $state = (new SearchFeedbackTelegramBotConversationState())
            ->setSearchTerm($searchTerm)
            ->setStep($stateStep)
        ;

        $conversation = $this->createConversation(SearchFeedbackTelegramBotConversation::class, $state);

        $this
            ->type($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }
}
