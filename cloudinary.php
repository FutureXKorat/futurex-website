<?php
declare(strict_types=1);

/**
 * Upload a slip image to Cloudinary.
 * Reads credentials from env vars: CLOUDINARY_CLOUD_NAME + CLOUDINARY_API_KEY + CLOUDINARY_API_SECRET
 * or from the combined CLOUDINARY_URL = cloudinary://api_key:api_secret@cloud_name
 * Returns the secure_url on success, null on failure.
 */
function uploadSlipToCloudinary(string $tmpFilePath, string $publicId): ?string {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: '';
    $apiKey    = getenv('CLOUDINARY_API_KEY') ?: '';
    $apiSecret = getenv('CLOUDINARY_API_SECRET') ?: '';

    if (!$cloudName) {
        $raw = getenv('CLOUDINARY_URL') ?: '';
        if (preg_match('#cloudinary://([^:]+):([^@]+)@(.+)#', $raw, $m)) {
            $apiKey = $m[1]; $apiSecret = $m[2]; $cloudName = $m[3];
        }
    }

    if (!$cloudName || !$apiKey || !$apiSecret) return null;

    $folder    = 'futurex/slips';
    $timestamp = time();
    // Signature: params sorted A-Z, concatenated key=value&..., then api_secret appended
    $sigStr    = "folder={$folder}&public_id={$publicId}&timestamp={$timestamp}{$apiSecret}";
    $signature = sha1($sigStr);

    $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'file'      => new CURLFile($tmpFilePath),
            'api_key'   => $apiKey,
            'timestamp' => (string)$timestamp,
            'public_id' => $publicId,
            'folder'    => $folder,
            'signature' => $signature,
        ],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    $data = json_decode((string)$res, true);
    return is_array($data) ? ($data['secure_url'] ?? null) : null;
}
