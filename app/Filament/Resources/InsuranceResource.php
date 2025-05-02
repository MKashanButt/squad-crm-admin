<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InsuranceResource\Pages;
use App\Models\Insurance;
use Filament\Forms;
// use Filament\Forms\Components\Builder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class InsuranceResource extends Resource
{
    protected static ?string $model = Insurance::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationGroup = 'References';
    protected static ?string $modelLabel = 'Insurance Provider';
    protected static ?string $pluralModelLabel = 'Insurance Providers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Insurance Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'This insurance provider already exists'
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('leads_count')
                    ->label('Associated Leads')
                    ->numeric()
                    ->sortable()
                    ->getStateUsing(fn(Insurance $record) => $record->formInputs()->count()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Edit Insurance'),

                Tables\Actions\Action::make('viewLeads')
                    ->label('View Leads')
                    ->url(fn(Insurance $record) => LeadsResource::getUrl('index', [
                        'tableFilters[insurance_id][value]' => $record->id
                    ]))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('View associated leads'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Prevent deletion if any insurance has associated leads
                            $hasLeads = $records->contains(fn($record) => $record->leads()->exists());
                            if ($hasLeads) {
                                throw new \Exception('Cannot delete insurance providers with associated leads');
                            }
                        }),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Insurance Provider'),
            ])
            ->persistSortInSession()
            ->deferLoading();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('formInputs');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInsurances::route('/'),
            'create' => Pages\CreateInsurance::route('/create'),
            'edit' => Pages\EditInsurance::route('/{record}/edit'),
        ];
    }
}
