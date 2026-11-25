<?php

declare(strict_types=1);

namespace App\Modules\Membership\Services;

use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Membership\Contracts\GoogleSheetsClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Isolated Google Sheets v4 API client for linked Google Form responses (Spec Section 5.3 & 5.4).
 */
class GoogleSheetsService implements GoogleSheetsClientInterface
{
    private const TOKEN_CACHE_KEY = 'pyc.integrations.google_sheets.access_token';

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchResponses(?string $sinceCursor = null): array
    {
        $connection = IntegrationConnection::query()->where('provider', 'google_sheets')->first();

        $spreadsheetId = trim((string) ($connection?->spreadsheet_id ?: config('pyc.membership.google_sheets.spreadsheet_id', '')));
        $range = trim((string) ($connection?->sheet_range ?: config('pyc.membership.google_sheets.sheet_range', 'Form Responses 1!A1:Z2000')));

        if ($spreadsheetId === '') {
            throw new RuntimeException('Google Sheets spreadsheet ID is not configured.');
        }

        $encodedRange = rawurlencode($range);
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$encodedRange}";

        $request = Http::timeout(25)->retry(2, 300, throw: false)->acceptJson();
        $accessToken = $this->resolveServiceAccountAccessToken();
        $apiKey = trim((string) config('pyc.membership.google_sheets.api_key', ''));

        if ($accessToken !== null) {
            $request = $request->withToken($accessToken);
        } elseif ($apiKey !== '') {
            $url .= '?key='.urlencode($apiKey);
        } else {
            throw new RuntimeException('Google Sheets credentials (service account JSON or API key) are not configured.');
        }

        $response = $request->get($url);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Google Sheets API request failed with HTTP '.$response->status().': '.mb_substr($response->body(), 0, 300)
            );
        }

        /** @var list<list<string>> $values */
        $values = (array) $response->json('values', []);
        if (count($values) <= 1) {
            return [];
        }

        $headers = array_map(static fn ($h): string => trim((string) $h), $values[0]);
        $startRowNumber = $sinceCursor !== null && ctype_digit($sinceCursor) ? ((int) $sinceCursor) + 1 : 2;

        $rows = [];
        $totalRows = count($values);

        for ($i = max(1, $startRowNumber - 1); $i < $totalRows; $i++) {
            $rawCells = $values[$i];
            if ($this->isEmptyRow($rawCells)) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $colIdx => $headerName) {
                if ($headerName === '') {
                    continue;
                }
                $assoc[$headerName] = isset($rawCells[$colIdx]) ? trim((string) $rawCells[$colIdx]) : '';
            }

            $sheetRowNumber = $i + 1;
            $externalId = $this->deriveExternalResponseId($spreadsheetId, $sheetRowNumber, $assoc);

            $rows[] = [
                'external_response_id' => $externalId,
                'row_number' => $sheetRowNumber,
                'payload' => $assoc,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<mixed>  $cells
     */
    private function isEmptyRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Generates a stable, deterministic external_response_id scoped to the spreadsheet.
     *
     * @param  array<string, string>  $assoc
     */
    private function deriveExternalResponseId(string $spreadsheetId, int $rowNumber, array $assoc): string
    {
        foreach (['Response ID', 'response_id', 'Submission ID'] as $explicitKey) {
            if (! empty($assoc[$explicitKey])) {
                return trim($assoc[$explicitKey]);
            }
        }

        $timestamp = $assoc['Timestamp'] ?? $assoc['timestamp'] ?? '';
        $identity = strtolower($assoc['Email Address'] ?? $assoc['Email'] ?? $assoc['Phone Number'] ?? $assoc['Phone'] ?? '');

        $fingerprint = substr(hash('sha256', "{$spreadsheetId}|{$rowNumber}|{$timestamp}|{$identity}"), 0, 24);

        return "gsheet-r{$rowNumber}-{$fingerprint}";
    }

    private function resolveServiceAccountAccessToken(): ?string
    {
        $jsonPath = trim((string) config('pyc.membership.google_sheets.service_account_json_path', ''));
        if ($jsonPath === '' || ! is_file($jsonPath)) {
            return null;
        }

        /** @var string|null $cached */
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $credentials = json_decode((string) file_get_contents($jsonPath), true);
        if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Invalid Google service account JSON file structure.');
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']) ?: '');
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/spreadsheets.readonly',
            'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]) ?: '');

        $signingInput = "{$header}.{$claims}";
        $signature = '';

        if (! openssl_sign($signingInput, $signature, (string) $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Failed to sign Google service account JWT assertion.');
        }

        $jwt = $signingInput.'.'.$this->base64UrlEncode($signature);

        $tokenResponse = Http::asForm()->timeout(15)->post(
            (string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token'),
            [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]
        );

        if (! $tokenResponse->successful()) {
            throw new RuntimeException('Failed to exchange Google service account JWT for access token.');
        }

        $accessToken = (string) $tokenResponse->json('access_token', '');
        if ($accessToken === '') {
            throw new RuntimeException('Empty access_token returned from Google OAuth endpoint.');
        }

        Cache::put(self::TOKEN_CACHE_KEY, $accessToken, now()->addMinutes(50));

        return $accessToken;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
