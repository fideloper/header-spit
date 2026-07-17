<?php

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('plain');
});

Route::get('/pause', function() {
	request()->validate([
		'seconds' => ['numeric'],
	]);
	$s = request()->get('seconds');

	sleep($s);

	return 'done';
});

/*
 * Simple read-only check against SQS to verify that temporary credentials
 * (e.g. from an EKS Pod Identity association) are being picked up correctly.
 *
 * No explicit credentials are configured: the SDK's default credential
 * provider chain automatically resolves Pod Identity creds via the
 * AWS_CONTAINER_CREDENTIALS_FULL_URI / AWS_CONTAINER_AUTHORIZATION_TOKEN_FILE
 * env vars that EKS injects into the pod (it also falls back to IRSA web
 * identity tokens and the instance/ECS metadata endpoints).
 */
Route::get('/sqs', function () {
    $queueUrl = 'https://sqs.us-east-2.amazonaws.com/905418265914/fidelopers-deletable-queue';

    $sqs = new SqsClient([
        'region' => 'us-east-2',
        'version' => 'latest',
    ]);

    try {
        // GetQueueAttributes is a read-only operation that requires the
        // sqs:GetQueueAttributes permission, so it confirms both that the
        // credentials resolved and that the role can read the queue.
        $result = $sqs->getQueueAttributes([
            'QueueUrl' => $queueUrl,
            'AttributeNames' => ['All'],
        ]);

        return response()->json([
            'ok' => true,
            'queue_url' => $queueUrl,
            'attributes' => $result->get('Attributes'),
        ]);
    } catch (AwsException $e) {
        return response()->json([
            'ok' => false,
            'queue_url' => $queueUrl,
            'aws_error_code' => $e->getAwsErrorCode(),
            'message' => $e->getAwsErrorMessage() ?: $e->getMessage(),
        ], 500);
    }
});

