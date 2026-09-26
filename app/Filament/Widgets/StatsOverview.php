<?php

namespace App\Filament\Widgets;

use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Subject;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $currentYearId = AcademicYear::where('is_current', true)->value('id');

        // Note: Students Model has a global scope for current academic year and only active students by default.
        // We'll use withoutGlobalScopes if we need raw counts.
        
        $totalStudents = Student::withoutGlobalScopes(['currentAcademicYear', 'onlyActiveStudents'])->count();
        $activeStudents = Student::withoutGlobalScopes(['onlyActiveStudents'])->where('is_active', true)->count();
        $inactiveStudents = Student::withoutGlobalScopes(['onlyActiveStudents'])->where('is_active', false)->count();

        $totalClasses = Classes::count(); // Global scope handles current academic year
        $totalExams = Exam::count();    // Global scope handles current academic year
        $totalSubjects = Subject::count();

        return [
            Stat::make('Total Students', $totalStudents)
                ->description('All registered students')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            Stat::make('Active Students', $activeStudents)
                ->description($inactiveStudents . ' Inactive')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Total Classes', $totalClasses)
                ->description('Current Academic Year')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('warning'),
            Stat::make('Total Exams', $totalExams)
                ->description('Current Academic Year')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),
        ];
    }
}
