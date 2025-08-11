<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeductedReturnResource\Pages;
use App\Models\DeductedReturn;
use App\Models\User;
use DB;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DeductedReturnResource extends Resource
{
    protected static ?string $model = DeductedReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-minus-circle';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationGroup = 'Lead Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        $isAdmin = auth()->user()->hasRole('admin');

        return $table
            ->query(
                DeductedReturn::query()->where('status', 'deducted return')
            )
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
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn() => !auth()->user()->can('update', DeductedReturn::class))
                    ->modalWidth('4xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->authorize(fn(User $user) => $user->hasRole('admin'))
                        ->action(function ($records) {
                            DB::transaction(function () use ($records) {
                                $records->chunk(500, function ($chunk) {
                                    DeductedReturn::whereIn('id', $chunk->pluck('id'))->delete();
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeductedReturns::route('/'),
            'create' => Pages\CreateDeductedReturn::route('/create'),
            'edit' => Pages\EditDeductedReturn::route('/{record}/edit'),
        ];
    }
}
