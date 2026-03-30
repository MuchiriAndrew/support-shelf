<?php

namespace App\Support;

class LandingPageDefaults
{
    /**
     * @return array{
     *     hero: array<string, string>,
     *     metrics: array<int, array<string, string>>,
     *     pillars: array<int, array<string, string>>,
     *     workflow: array<int, array<string, string>>,
     *     showcases: array<int, array<string, string|null>>,
     *     proof_points: array<int, string>,
     *     cta: array<string, string>
     * }
     */
    public static function content(): array
    {
        return [
            'hero' => [
                'kicker' => 'Private assistant platform',
                'title' => 'Turn your documents and websites into an assistant that knows your world.',
                'description' => 'SupportShelf gives every user a private workspace for source ingestion, semantic retrieval, and grounded conversations, all wrapped in a polished product experience.',
            ],
            'metrics' => [
                [
                    'label' => 'Private by default',
                    'value' => 'One isolated assistant per account',
                ],
                [
                    'label' => 'Knowledge sources',
                    'value' => 'Upload files and crawl full websites',
                ],
                [
                    'label' => 'Grounded responses',
                    'value' => 'Answers stay tied to your own context',
                ],
            ],
            'pillars' => [
                [
                    'title' => 'Private knowledge, grounded answers',
                    'description' => 'Each account builds answers from its own uploaded documents and crawled URLs, so responses stay aligned with that user\'s data.',
                ],
                [
                    'title' => 'One workspace per user',
                    'description' => 'Every user gets an isolated AI workspace where documents, links, vectors, and conversations remain private to that account.',
                ],
                [
                    'title' => 'Custom assistant behavior',
                    'description' => 'Users can tailor their assistant name and instructions while the platform keeps the output grounded in retrieved context.',
                ],
            ],
            'workflow' => [
                [
                    'title' => 'Ingest your context',
                    'description' => 'Drop in documents or point the platform at a website, and the knowledge base starts building around your own material.',
                ],
                [
                    'title' => 'Index it privately',
                    'description' => 'Content is chunked, embedded, and stored for retrieval in a workspace scoped to the current user only.',
                ],
                [
                    'title' => 'Chat with your assistant',
                    'description' => 'Ask questions in a modern chat UI and get responses grounded in the sources you uploaded, not public guesswork.',
                ],
            ],
            'showcases' => [
                [
                    'eyebrow' => 'Source ingestion demo',
                    'title' => 'Show how websites and files land inside the knowledge base',
                    'description' => 'Use this section for a short recording of website ingestion, uploads, and the knowledge library updating after processing.',
                    'video_path' => null,
                    'placeholder' => 'Add an ingestion walkthrough video here',
                ],
                [
                    'eyebrow' => 'Assistant chat demo',
                    'title' => 'Show the assistant answering from private context',
                    'description' => 'Record a chat session that demonstrates grounded answers, history, and the polished customer-facing experience.',
                    'video_path' => null,
                    'placeholder' => 'Add a chat demo video here',
                ],
            ],
            'proof_points' => [
                'Websites and uploaded documents feed the same private retrieval layer.',
                'Assistant identity and behavior can be tailored per user from the admin panel.',
                'Realtime chat makes the product feel responsive instead of feeling like a static demo.',
                'The product is already structured for a SaaS model with per-user isolation across storage, retrieval, and conversation history.',
            ],
            'cta' => [
                'kicker' => 'Ready to use it?',
                'title' => 'Create an assistant, ingest your sources, and start chatting from your own knowledge.',
                'description' => 'The platform already supports website crawling, document uploads, vector retrieval, realtime chat, and per-user assistant customization.',
            ],
        ];
    }
}
