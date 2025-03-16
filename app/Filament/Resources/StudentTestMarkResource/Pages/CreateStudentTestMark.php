<?php
namespace App\Filament\Resources\StudentTestMarkResource\Pages;

use App\Filament\Resources\StudentTestMarkResource;
use App\Models\StudentTestMark;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
class CreateStudentTestMark extends CreateRecord
{
    protected static string $resource = StudentTestMarkResource::class;
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('Cancel')
                ->label('Cancel')
                ->icon('heroicon-o-arrow-left')
                ->url($this->getResource()::getUrl('index'))// Sirf "Create" button dikhai dega
        ];
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $students = $data['test_marks'] ?? [];

        DB::transaction(function () use ($students, $data) {
            foreach ($students as $student) {
                StudentTestMark::create([
                    'test_name' => $data['test_name'] ?? null,
                    'student_id' => $student['student_id'],
                    'class_id' => $data['class_id'] ?? null,
                    'term_id' => $data['term_id'] ?? null,
                    'obtain_number' => $student['obtain_number'] ?? 0,
                    'subject_number' => $data['subject_number'] ?? 0,
                    'created_at' => now(),
                ]);
            }
        });

        $this->notifyAndRedirect();
        $this->halt();
        return $data;
    }
    private function notifyAndRedirect(): void
    {
        Notification::make()
            ->success()
            ->title('Record Saved')
            ->body('Class test masks have been saved successfully!')
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }

}
