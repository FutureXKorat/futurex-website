<?php
declare(strict_types=1);

/**
 * Shared Cloudinary upload helper.
 * Reads credentials from CLOUDINARY_CLOUD_NAME + CLOUDINARY_API_KEY + CLOUDINARY_API_SECRET
 * or the combined CLOUDINARY_URL = cloudinary://api_key:api_secret@cloud_name
 * Returns the secure_url on success, null on failure.
 */
function _cloudinaryUpload(string $tmpFilePath, string $publicId, string $folder): ?string {
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

    $timestamp = time();
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

    $data = json_decode((string)$res, true);
    return is_array($data) ? ($data['secure_url'] ?? null) : null;
}

function uploadSlipToCloudinary(string $tmpFilePath, string $publicId): ?string {
    return _cloudinaryUpload($tmpFilePath, $publicId, 'futurex/slips');
}

function uploadProfilePicToCloudinary(string $tmpFilePath, string $publicId): ?string {
    return _cloudinaryUpload($tmpFilePath, $publicId, 'futurex/profile_pics');
}

function uploadProductImageToCloudinary(string $tmpFilePath, string $publicId): ?string {
    return _cloudinaryUpload($tmpFilePath, $publicId, 'futurex/products');
}
