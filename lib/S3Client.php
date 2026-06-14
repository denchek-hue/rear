<?php
/**
 * Thin wrapper around the AWS SDK S3 client.
 * Reads credentials from environment variables:
 *   BUCKET, REGION, ENDPOINT, ACCESS_KEY_ID, SECRET_ACCESS_KEY
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

function getS3Client(): S3Client
{
    return new S3Client([
        'version'                 => 'latest',
        'region'                  => getenv('REGION') ?: 'us-east-1',
        'endpoint'                => getenv('ENDPOINT'),
        'use_path_style_endpoint' => true,
        'credentials'             => [
            'key'    => getenv('ACCESS_KEY_ID'),
            'secret' => getenv('SECRET_ACCESS_KEY'),
        ],
    ]);
}

/**
 * Upload a local temp file to S3 and return the public URL.
 *
 * @param  string $tmpPath   Path to the uploaded temp file ($_FILES[...]['tmp_name'])
 * @param  string $filename  Destination object key (e.g. "bag_1234_567.jpg")
 * @param  string $mimeType  MIME type of the file
 * @return string            Public URL of the uploaded object
 * @throws RuntimeException  On upload failure
 */
function s3Upload(string $tmpPath, string $filename, string $mimeType): string
{
    $bucket = getenv('BUCKET');
    if (!$bucket) {
        throw new RuntimeException('S3 BUCKET environment variable is not set.');
    }

    $s3 = getS3Client();

    try {
        $s3->putObject([
            'Bucket'      => $bucket,
            'Key'         => $filename,
            'SourceFile'  => $tmpPath,
            'ContentType' => $mimeType,
            'ACL'         => 'public-read',
        ]);
    } catch (AwsException $e) {
        throw new RuntimeException('S3 upload failed: ' . $e->getMessage());
    }

    // Build the public URL from the endpoint + bucket + key
    $endpoint = rtrim(getenv('ENDPOINT'), '/');
    return $endpoint . '/' . $bucket . '/' . $filename;
}

/**
 * Delete an object from S3 by its full URL or just its key.
 *
 * @param  string $url  Full S3 URL as stored in the database
 */
function s3Delete(string $url): void
{
    $bucket = getenv('BUCKET');
    if (!$bucket) {
        return;
    }

    // Extract the object key from the URL: everything after "/<bucket>/"
    $prefix = '/' . $bucket . '/';
    $pos    = strpos($url, $prefix);
    if ($pos === false) {
        return; // Not an S3 URL we recognise — skip silently
    }
    $key = substr($url, $pos + strlen($prefix));

    $s3 = getS3Client();

    try {
        $s3->deleteObject([
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);
    } catch (AwsException $e) {
        // Log but don't hard-fail on delete errors
        error_log('S3 delete failed for key "' . $key . '": ' . $e->getMessage());
    }
}
