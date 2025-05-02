<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BadLeadsResource\Pages;
use App\Models\BadLeads;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BadLeadsResource extends Resource
{
    protected static ?string $model = BadLeads::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'Lead Management';

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Empty form for read-only resource
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'id',
                'status',
                'user_id',
                'insurance_id',
                'products_id',
                'patient_phone',
                'first_name',
                'last_name',
                'created_at',
                'city',
                'state',
                'medicare_id'
            ])
            ->with([
                'user:id,name,team',
                'insurance:id,name',
                'products:id,name'
            ])
            ->where('status', 'bad lead')
            ->orderBy('id', 'desc')
            ->when(!auth()->user()->hasRole('admin'), function (Builder $query) {
                $query->where('user_id', auth()->id())
                    ->when(
                        auth()->user()->hasRole('manager'),
                        fn(Builder $q) => $q->orWhereHas(
                            'user',
                            fn(Builder $userQuery) => $userQuery->where(
                                'team',
                                strtolower(auth()->user()->name)
                            )
                        )
                    );
            });
    }

    public static function table(Table $table): Table
    {
        $isAdmin = auth()->user()->hasRole('admin');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->description(fn($record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Agent')
                    ->formatStateUsing(fn($state) => $state ? ucwords($state) : 'N/A')
                    ->toggleable(isToggledHiddenByDefault: !$isAdmin)
                    ->sortable()
                    ->searchable(),
                $isAdmin ?
                    Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'new' => 'New',
                        'paid' => 'Paid',
                        'payable' => 'Payable',
                        'returned' => 'Returned',
                    ])
                    :
                    Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'bad lead' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn($state) => ucwords($state))
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('insurance.name')
                    ->label('Insurance')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('products.name')
                    ->label('Product')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient_phone')
                    ->label('Phone')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),

                Tables\Columns\TextColumn::make('first_name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),

                Tables\Columns\TextColumn::make('medicare_id')
                    ->label('Medicare ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('city')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('state')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('team')
                    ->label('Agent Team')
                    ->options(
                        Cache::remember('team_filter_options', 3600, function () {
                            return User::query()
                                ->select('team')
                                ->whereNotNull('team')
                                ->distinct()
                                ->orderBy('team')
                                ->pluck('team', 'team')
                                ->mapWithKeys(fn($team) => [
                                    $team => ucwords(strtolower($team))
                                ]);
                        })
                    )
                    ->searchable()
                    ->preload()
                    ->visible(fn() => auth()->user()->hasRole('admin')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn() => !auth()->user()->can('update', BadLeads::class))
                    ->modalWidth('4xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->authorize(fn(User $user) => $user->hasRole('admin'))
                        ->action(function ($records) {
                            DB::transaction(function () use ($records) {
                                $records->chunk(500, function ($chunk) {
                                    BadLeads::whereIn('id', $chunk->pluck('id'))->delete();
                                });
                            });
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Leads')
                        ->modalDescription('Are you sure you want to delete these leads? This cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                ])->label('Bulk Actions'),
            ])
            ->emptyStateActions([])
            ->defaultSort('id', 'desc')
            ->deferLoading()
            ->persistFiltersInSession()
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBadLeads::route('/'),
            'edit' => Pages\EditBadLeads::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }
}
