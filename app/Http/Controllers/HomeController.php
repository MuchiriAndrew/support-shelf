<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'pillars' => [
                [
                    'title' => 'Grounded product answers',
                    'description' => 'Customers can ask about setup, compatibility, returns, and policies without digging through help-center pages themselves.',
                ],
                [
                    'title' => 'One support library',
                    'description' => 'Manuals, policy documents, and support articles are pulled into one searchable knowledge base instead of living in separate silos.',
                ],
                [
                    'title' => 'Cleaner support operations',
                    'description' => 'Teams get a reusable assistant experience that stays consistent across product questions and post-purchase help.',
                ],
            ],
            'foundation' => [
                'The chat experience is designed to feel like a familiar support conversation instead of a developer demo.',
                'Answers are shaped around your own support content so product details and store policies stay consistent.',
                'Ingestion tools let you grow the knowledge base over time by adding websites, manuals, and policy documents.',
            ],
            'journey' => [
                'Bring in help-center pages and support files.',
                'Turn that content into a searchable support library.',
                'Let customers ask questions in a clean, conversational interface.',
            ],
        ]);
    }
}
