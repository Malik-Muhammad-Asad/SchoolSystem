<?php

namespace App\Filament\Pages;

use App\Models\SchoolSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class SchoolInformation extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static string $view = 'filament.pages.school-information';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'School Information';
    protected static ?string $title = 'School Information';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SchoolSetting::first();
        
        if ($settings) {
            $this->form->fill($settings->toArray());
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('General Information')
                    ->schema([
                        TextInput::make('school_name')
                            ->label('School Name'),
                        TextInput::make('school_address')
                            ->label('Address'),
                        TextInput::make('school_phone')
                            ->label('Phone Number'),
                        TextInput::make('school_email')
                            ->label('Email'),
                    ])->columns(2),

                Section::make('Report Images')
                    ->schema([
                        FileUpload::make('header_image')
                            ->label('Report Header')
                            ->image()
                            ->disk('public')
                            ->directory('school-settings'),
                        FileUpload::make('signature_image')
                            ->label('Report Signature')
                            ->image()
                            ->disk('public')
                            ->directory('school-settings'),
                        FileUpload::make('watermark_image')
                            ->label('Report Watermark')
                            ->image()
                            ->disk('public')
                            ->directory('school-settings'),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->action(fn () => $this->save()),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $settings = SchoolSetting::first();
        
        if ($settings) {
            $settings->update($data);
        } else {
            $settings = SchoolSetting::create($data);
        }

        $this->form->fill($settings->toArray());

        Notification::make()
            ->title('School information saved successfully!')
            ->success()
            ->send();
    }
}
