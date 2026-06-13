<?php

namespace Webkul\Project\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Webkul\Project\Models\Timesheet;

class TopAssigneesWidget extends BaseWidget
{
    use HasWidgetShield, InteractsWithPageFilters;

    protected static ?string $pollingInterval = '15s';

    protected static bool $isLazy = false;

    protected static function getPagePermission(): ?string
    {
        return 'widget_project_top_assignees_widget';
    }

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('projects::filament/widgets/top-assignees.heading.title');
    }

    public function getTableRecordKey(Model|array $record): string
    {
        return 'id';
    }

    public function table(Table $table): Table
    {
        $startDate = ! is_null($this->pageFilters['startDate'] ?? null) ?
            Carbon::parse($this->pageFilters['startDate']) :
            null;

        $endDate = ! is_null($this->pageFilters['endDate'] ?? null) ?
            Carbon::parse($this->pageFilters['endDate']) :
            now();

        $subQuery = Timesheet::query()
            ->join('users', 'users.id', '=', 'analytic_records.user_id')
            ->selectRaw('
                analytic_records.user_id as id,
                users.name as user_name,
                SUM(analytic_records.unit_amount) as total_hours,
                COUNT(DISTINCT analytic_records.task_id) as total_tasks
            ')
            ->whereBetween('analytic_records.created_at', [$startDate, $endDate])
            ->groupBy('analytic_records.user_id', 'users.name');

        if (! empty($this->pageFilters['selectedProjects'])) {
            $subQuery->whereIn('analytic_records.project_id', $this->pageFilters['selectedProjects']);
        }

        if (! empty($this->pageFilters['selectedAssignees'])) {
            $subQuery->whereIn('analytic_records.user_id', $this->pageFilters['selectedAssignees']);
        }

        if (! empty($this->pageFilters['selectedPartners'])) {
            $subQuery->whereIn('analytic_records.partner_id', $this->pageFilters['selectedPartners']);
        }

        $query = Timesheet::query()
            ->fromSub($subQuery, 'top_assignees')
            ->orderByDesc('total_hours')
            ->limit(10);

        $query->getModel()->setTable('top_assignees');
        $query->getModel()->setKeyName('id');

        return $table
            ->query($query)
            ->paginated(false)
            ->columns([
                TextColumn::make('user_name')
                    ->label(__('projects::filament/widgets/top-assignees.table-columns.user'))
                    ->sortable(),
                TextColumn::make('total_hours')
                    ->label(__('projects::filament/widgets/top-assignees.table-columns.hours-spent'))
                    ->sortable(),
                TextColumn::make('total_tasks')
                    ->label(__('projects::filament/widgets/top-assignees.table-columns.tasks'))
                    ->sortable(),
            ]);
    }
}
