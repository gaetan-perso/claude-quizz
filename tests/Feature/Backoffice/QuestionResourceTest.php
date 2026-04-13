<?php declare(strict_types=1);

use App\Enums\Difficulty;
use App\Enums\UserRole;
use App\Filament\Pages\GenerateQuestions;
use App\Filament\Resources\QuestionResource;
use App\Jobs\GenerateQuestionsJob;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin    = User::factory()->create(['role' => UserRole::Admin]);
    $this->category = Category::factory()->create();
    $this->actingAs($this->admin);
});

it('lists questions with filters', function () {
    Question::factory()->count(5)->create(['category_id' => $this->category->id]);

    livewire(QuestionResource\Pages\ListQuestions::class)
        ->assertCanSeeTableRecords(Question::all());
});

it('admin can create an open question manually', function () {
    livewire(QuestionResource\Pages\CreateQuestion::class)
        ->set('data.category_id', $this->category->id)
        ->set('data.text', 'Quelle est la capitale de la France ?')
        ->set('data.difficulty', Difficulty::Easy->value)
        ->set('data.type', 'open')
        ->set('data.estimated_time_seconds', 20)
        ->set('data.is_active', true)
        ->call('create')
        ->assertHasNoErrors();

    expect(Question::where('text', 'Quelle est la capitale de la France ?')->exists())->toBeTrue();
});

it('dispatches GenerateQuestionsJob when AI generation is submitted', function () {
    Queue::fake();

    livewire(GenerateQuestions::class)
        ->set('data.topic', 'Histoire de France')
        ->set('data.category_id', $this->category->id)
        ->set('data.difficulty', Difficulty::Medium->value)
        ->set('data.count', 5)
        ->call('generate');

    Queue::assertPushed(GenerateQuestionsJob::class, function ($job) {
        return $job->topic === 'Histoire de France' && $job->count === 5;
    });
});
