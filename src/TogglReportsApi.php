<?php

namespace MorningTrain\TogglApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Wrapper for the Toggl Reports Api.
 *
 * @see https://github.com/toggl/toggl_api_docs/blob/master/reports.md
 */
class TogglReportsApi
{

    /**
     * @var string
     */
    protected $apiToken = '';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $v2Client;

    protected ?Client $v3Client = null;

    /**
     * TogglReportsApi constructor.
     *
     * @param string $apiToken
     */
    public function __construct(
        string $apiToken,
        ?string $workspaceID = null
    ) {
        $this->apiToken = $apiToken;
        $this->v2Client = new Client([
            'base_uri' => 'https://api.track.toggl.com/reports/api/v2/',
            'auth' => [$this->apiToken, 'api_token'],
        ]);

        if($workspaceID) {
            $this->v3Client = new Client( [
                'base_uri' => 'https://api.track.toggl.com/reports/api/v3/workspace/' . $workspaceID,
                'auth'         => [ $this->apiToken, 'api_token' ],
            ]);
        }
    }

    public function searchTimeEntries(array $query, array $options = [])
    {
        $options['version'] = 3;
        return $this->POST('search/time_entries', $query, $options);

    }

    /**
     * Get available endpoints.
     *
     * @return bool|mixed|object
     */
    public function getAvailableEndpoints()
    {
        return $this->get('');
    }

    /**
     * Get project report.
     *
     * @param array $query
     *
     * @param array $options
     * @return bool|mixed|object
     */
    public function getProjectReport($query, $options = array())
    {
        return $this->get('project', $query, $options);
    }

    /**
     * Get summary report.
     *
     * @param array $query
     *
     * @param array $options
     * @return bool|mixed|object
     */
    public function getSummaryReport($query, $options = array())
    {
        return $this->get('summary', $query, $options);
    }

    /**
     * Get details report.
     *
     * @param array $query
     *
     * @param array $options
     * @return bool|mixed|object
     */
    public function getDetailsReport($query, $options = array())
    {
        return $this->get('details', $query, $options);
    }

    /**
     * Get weekly report.
     *
     * @param array $query
     *
     * @param array $options
     * @return bool|mixed|object
     */
    public function getWeeklyReport($query, $options = array())
    {
        return $this->get('weekly', $query, $options);
    }

    /**
     * Helper for client get command.
     *
     * @param string $endpoint
     * @param array $query
     *
     * @param array $options
     * @return bool|mixed|object
     */
    private function GET($endpoint, $query = array(), $options = array())
    {
        try {
            $response = $this->v2Client->get($endpoint, [ 'query' => $query]);

            return $this->checkResponse($response, !($options['getFullResponse'] ?? false));
        } catch (ClientException $e) {
            return (object) [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper for client post command.
     *
     * @param string $endpoint
     * @param array $query
     *
     * @param array $options
     * @return bool|mixed|object
     */
    private function POST($endpoint, $query = array(), $options = array())
    {
        $client = ($options['version'] ?? 2) === 3 ? $this->v3Client : $this->v2Client;
        if(!$client){
            return false;
        }
        try {
            $response = $client->post($endpoint, [ 'query' => $query]);

            return $this->checkResponse($response, !($options['getFullResponse'] ?? false));
        } catch (ClientException $e) {
            return (object) [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper for client put command.
     *
     * @param string $endpoint
     * @param array $query
     *
     * @param array $options
     * @return bool|mixed|object
     */
    private function PUT($endpoint, $query = array(), $options = array())
    {
        try {
            $response = $this->v2Client->put($endpoint, [ 'query' => $query]);

            return $this->checkResponse($response, !($options['getFullResponse'] ?? false));
        } catch (ClientException $e) {
            return (object) [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper for client delete command.
     *
     * @param string $endpoint
     * @param array $query
     *
     * @param array $options
     * @return bool|mixed|object
     */
    private function DELETE($endpoint, $query = array(), $options = array())
    {
        try {
            $response = $this->v2Client->delete($endpoint, [ 'query' => $query]);

            return $this->checkResponse($response,!($options['getFullResponse'] ?? false));
        } catch (ClientException $e) {
            return (object) [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper for checking http response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param bool $returnDataOnly
     *
     * @return bool|mixed
     */
    private function checkResponse($response, $returnDataOnly = true)
    {
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), false);
            if ($returnDataOnly && $this->hasData($data)) {
                $data = $data->data;
            }

            return $data;
        }

        return false;
    }

    /**
     * @param $data
     * @return bool
     */
    private function hasData($data)
    {
        return (isset($data->data) && is_object($data));
    }
}
