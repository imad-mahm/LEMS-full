<?php
require 'vendor/autoload.php';
use OpenAI\Client;

function summarizeReviews($reviews) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Initialize OpenAI client
    $client = OpenAI::client($_ENV['OPENAI_API_KEY']);

    // Prepare the reviews text
    $reviewsText = "";
    foreach ($reviews as $review) {
        $reviewsText .= "Rating: " . $review['RATING'] . "/5\n";
        $reviewsText .= "Review: " . $review['CONTENT'] . "\n\n";
    }

    // Create the prompt for OpenAI
    $prompt = "You are an expert event reviewer and writer. You will receive a list of reviews written by attendees of an event. Your task is to summarize these reviews into one well-written, balanced, and engaging summary that reflects the overall sentiment, highlights common themes, praises, and criticisms, and reads as if written by a professional reviewer.

Instructions:

Combine all major points from the reviews into one cohesive review.

Reflect the overall tone (positive, negative, or mixed).

Include commonly mentioned strengths and weaknesses.

Use natural, fluent language suitable for publication on a website or report. the reviews are: " . $reviewsText . " Limit the summary to 70 words.";

    try {
        // Call OpenAI API
        $response = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that summarizes event reviews.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 100
        ]);

        return $response->choices[0]->message->content;
    } catch (Exception $e) {
        return "Unable to generate summary at this time.";
    }
}
?> 