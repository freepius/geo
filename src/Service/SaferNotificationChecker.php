<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service to check SAFER notifications for specific communes in Drôme (26).
 */
class SaferNotificationChecker
{
    private const BASE_URL = 'https://iframe-annonces-legales.safer.fr/ara/26/liste/notification';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    /**
     * Check if there are notifications for the given communes.
     *
     * @param array $communes List of commune names to check
     * @return array Array of commune names with their notification data
     *               Format: ['commune' => ['url' => '...', 'count' => 1, 'isNew' => true]]
     */
    public function checkNotifications(array $communes): array
    {
        $response = $this->httpClient->request('GET', self::BASE_URL);
        $html = $response->getContent();

        $results = [];

        foreach ($communes as $commune) {
            $notificationData = $this->findNotificationData($html, $commune);

            if ($notificationData !== null) {
                $results[$commune] = $notificationData;
            }
        }

        return $results;
    }

    /**
     * Find notification data for a specific commune in the HTML content.
     *
     * @param string $html HTML content
     * @param string $commune Commune name
     * @return array|null Notification data or null if not found
     *                    Format: ['url' => '...', 'count' => 1, 'isNew' => true]
     */
    private function findNotificationData(string $html, string $commune): ?array
    {
        // Pattern to match the entire <li> block containing the commune
        // Example:
        // <li>
        //     <a href="/ara/26/commune/26129/notification">EYMEUX</a>
        //     <span class="badge badge-warning">1</span>
        //     &nbsp;<span class="nouveau badge badge-danger badge-orange">Nouveau</span>
        // </li>
        $pattern = '/<li[^>]*>\s*'
            . '<a\s+href="([^"]+)"[^>]*>\s*' . preg_quote($commune, '/') . '\s*<\/a>'
            . '.*?'
            . '<span\s+class="[^"]*badge[^"]*"[^>]*>(\d+)<\/span>'
            . '(.*?)'
            . '<\/li>/is';

        if (preg_match($pattern, $html, $matches)) {
            $url = $matches[1];
            $count = (int) $matches[2];
            $restOfContent = $matches[3];

            // Check if "Nouveau" badge is present
            $isNew = preg_match('/<span[^>]*class="[^"]*nouveau[^"]*"[^>]*>Nouveau<\/span>/i', $restOfContent) === 1;

            // If the URL is relative, make it absolute
            if (!str_starts_with($url, 'http')) {
                $url = 'https://iframe-annonces-legales.safer.fr' . $url;
            }

            return [
                'url' => $url,
                'count' => $count,
                'isNew' => $isNew,
            ];
        }

        return null;
    }
}
