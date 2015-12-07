<?php

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;

class UsersList
{
    private $fb;

    /**
     * @param Facebook $fb
     */
    public function __construct(Facebook $fb)
    {
        $this->fb = $fb;
    }

    /**
     * @param string $eventId
     * @param string $accessToken
     *
     * @return array
     * @throws UsersListException
     */
    public function get($eventId, $accessToken)
    {
        $result = $this->getPage($eventId, $accessToken);
        sort($result);

        return $result;
    }

    private function getPage($eventId, $accessToken, $page = null)
    {
        $url = sprintf('/%s/attending', $eventId);
        if ($page) {
            $url .= '?after=' . $page;
        }

        try {
            $response = $this->fb->get($url, $accessToken);
        } catch(FacebookResponseException $e) {
            throw new UsersListException('Graph returned an error: ' . $e->getMessage());
        } catch(FacebookSDKException $e) {
            throw new UsersListException('Facebook SDK returned an error: ' . $e->getMessage());
        }

        $response = $response->getDecodedBody();

        $data = $this->formatData($response['data']);

        return isset($response['paging']['next']) ?
            array_merge($data, $this->getPage($eventId, $accessToken, $response['paging']['cursors']['after'])) :
            $data;
    }

    private function formatData($data)
    {
        $result = [];
        foreach ($data as $row) {
            $name = explode(' ', $row['name']);
            $result[] = array_pop($name) . ' ' . implode (' ', $name);
        }

        return $result;
    }
}
