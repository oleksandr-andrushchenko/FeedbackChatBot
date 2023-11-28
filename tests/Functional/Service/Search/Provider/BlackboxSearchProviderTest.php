<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Entity\Search\Blackbox\BlackboxFeedback;
use App\Entity\Search\Blackbox\BlackboxFeedbacks;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Tests\Traits\Search\SearchProviderTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Generator;
use DateTimeImmutable;

class BlackboxSearchProviderTest extends KernelTestCase
{
    use SearchProviderTrait;

    protected static SearchProviderName $searchProviderName = SearchProviderName::blackbox;

    public function supportsDataProvider(): Generator
    {
        yield 'not supported type' => [
            'type' => SearchTermType::facebook_username,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'person name & not ukr' => [
            'type' => SearchTermType::person_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'person name & ok' => [
            'type' => SearchTermType::person_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'phone number & not ukr' => [
            'type' => SearchTermType::phone_number,
            'term' => '380969603103',
            'context' => [],
            'expected' => false,
        ];

        yield 'phone number & not ukr code' => [
            'type' => SearchTermType::phone_number,
            'term' => '15613145672',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'phone number & ok' => [
            'type' => SearchTermType::phone_number,
            'term' => '380969603103',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];
    }

    public function searchDataProvider(): Generator
    {
        yield 'person name & surname only & many matches' => [
            'type' => SearchTermType::person_name,
            'term' => 'Черненко',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new BlackboxFeedbacks([
                    new BlackboxFeedback(
                        'Черненко Олена',
                        'https://blackbox.net.ua/0671402141',
                        '0671402141',
                        phoneFormatted: '+38 (067) 140-21-41',
                        comment: 'Клиент не забрал груз. Отправитель понес убытки за транспортировку.',
                        date: new DateTimeImmutable('2020-09-18'),
                        city: 'Ірпінь',
                        warehouse: 'Відділення №5 (до 30 кг): вул. Ново-Оскольська, 6-а, прим. №1001',
                        cost: '41',
                        type: 'Нова Пошта'
                    ),
                ]),
            ],
        ];

        yield 'person name & surname and name & many matches' => [
            'type' => SearchTermType::person_name,
            'term' => 'Андрущенко Володимир',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new BlackboxFeedbacks([
                    new BlackboxFeedback(
                        'Андрущенко Алексей',
                        'https://blackbox.net.ua/0932300040',
                        '0932300040',
                        phoneFormatted: '+38 (093) 230-00-40',
                        comment: 'Клиент не забрал посылку. Не отвечал на звонки и сообщения. Были понесены убытки на возврате отправления. Будьте внимательны с этим клиентом',
                        date: new DateTimeImmutable('2021-11-13'),
                        city: 'Бровари',
                        warehouse: 'Відділення №14 (до 30 кг на одне місце): бульв. Незалежності, 16',
                        cost: '46',
                        type: 'Нова Пошта'
                    ),
                ]),
            ],
        ];

        yield 'phone number & single match' => [
            'type' => SearchTermType::phone_number,
            'term' => '380932300040',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new BlackboxFeedback(
                    'Андрущенко Алексей',
                    'https://blackbox.net.ua/0932300040',
                    '0932300040',
                    phoneFormatted: '+38 (093) 230-00-40',
                    comment: 'Клиент не забрал посылку. Не отвечал на звонки и сообщения. Были понесены убытки на возврате отправления. Будьте внимательны с этим клиентом',
                    date: new DateTimeImmutable('2021-11-13'),
                    city: 'Бровари',
                    warehouse: 'Відділення №14 (до 30 кг на одне місце): бульв. Незалежності, 16',
                    cost: '46',
                    type: 'Нова Пошта'
                ),
            ],
        ];
    }
}