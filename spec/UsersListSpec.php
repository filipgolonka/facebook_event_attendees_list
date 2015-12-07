<?php

namespace spec;

use Facebook\Facebook;
use Facebook\FacebookResponse;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use UsersList;

/**
 * @mixin UsersList
 */
class UsersListSpec extends ObjectBehavior
{
    const ACCESS_TOKEN = 'accessToken';

    function let(Facebook $fb)
    {
        $this->beConstructedWith($fb);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('UsersList');
    }

    function it_returns_users_list(Facebook $fb, FacebookResponse $response)
    {
        $response->getDecodedBody()->willReturn([
            'data' => [
                ['name' => 'John Doe'],
                ['name' => 'Peter Falk'],
                ['name' => 'James Bond'],
            ],
        ]);
        $fb->get('/eventId/attending', self::ACCESS_TOKEN)->willReturn($response);

        $this->get('eventId', self::ACCESS_TOKEN)->shouldReturn([
            'Bond James',
            'Doe John',
            'Falk Peter',
        ]);
    }

    function it_fetches_data_from_next_page(
        Facebook $fb,
        FacebookResponse $firstPageResponse,
        FacebookResponse $secondPageResponse
    ) {
        $firstPageResponse->getDecodedBody()->willReturn([
            'data' => [
                ['name' => 'John Doe'],
                ['name' => 'Peter Falk'],
                ['name' => 'James Bond'],
            ],
            'paging' => [
                'next' => 'nextPageUrl',
                'cursors' => [
                    'after' => 'nextPageId',
                ],
            ],
        ]);
        $fb->get('/eventId/attending', self::ACCESS_TOKEN)->willReturn($firstPageResponse);

        $secondPageResponse->getDecodedBody()->willReturn([
            'data' => [
                ['name' => 'Bruce Lee'],
                ['name' => 'Sean Connery'],
            ],
        ]);
        $fb->get('/eventId/attending?after=nextPageId', self::ACCESS_TOKEN)->willReturn($secondPageResponse);

        $this->get('eventId', self::ACCESS_TOKEN)->shouldReturn([
            'Bond James',
            'Connery Sean',
            'Doe John',
            'Falk Peter',
            'Lee Bruce',
        ]);
    }

    function it_throws_an_exception_when_can_not_fetch_data_from_facebook(Facebook $fb)
    {
        $fb->get('/eventId/attending', self::ACCESS_TOKEN)->willThrow('Facebook\Exceptions\FacebookSDKException');

        $this->shouldThrow('UsersListException')->duringGet('eventId', self::ACCESS_TOKEN);
    }
}
