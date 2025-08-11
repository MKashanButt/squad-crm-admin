<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayableResource\Pages;
use App\Filament\Resources\PayableResource\RelationManagers;
use App\Models\Payable;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PayableResource extends Resource
{
    protected static ?string $model = Payable::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Payable';
    protected static ?string $pluralLabel = 'Payable';
    protected static ?string $navigationGroup = 'Lead Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

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
                'dob',
                'medicare_id',
                'city',
                'state',
                'doctor_name',
                'patient_last_visit'
            ])
            ->with(['user:id,name,team', 'insurance:id,name', 'products:id,name'])
            ->where('status', 'payable')
            ->orderBy('id', 'desc')
            ->when(request()->has('filters.team'), function (Builder $query) {
                $query->whereHas('user', fn($q) => $q->where('team', request('filters.team')));
            })
            ->when(!$isAdmin && !$user->hasRole('hr') && !$user->hasRole('qa'), function (Builder $query) use ($user) {
                $query->where('user_id', $user->id)
                    ->when($user->hasRole('manager'), function (Builder $q) use ($user) {
                        $q->orWhereHas('user', fn($q) => $q->where('team', strtolower($user->name)));
                    });
            });
    }

    public static function table(Table $table): Table
    {
        $isAdmin = Auth::user()->hasRole('admin');
        $isHr = Auth::user()->hasRole('hr');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->description(fn($record) => $record->created_at->diffForHumans())
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Agent')
                    ->formatStateUsing(fn($state) => $state ? ucwords($state) : 'N/A')
                    ->toggleable(isToggledHiddenByDefault: !($isAdmin || $isHr))
                    ->sortable()
                    ->searchable(),
                $isAdmin ?
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'new' => 'New',
                        'paid' => 'Paid',
                        'bad lead' => 'Bad Lead',
                        'returned' => 'Returned',
                        'deducted returns' => 'Deducted Returns'
                    ])
                : Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state instanceof \App\Enum\InputStatus ? $state->value : $state) {
                        'bad lead' => 'danger',
                        'paid' => 'success',
                        default => 'primary'
                    })
                    ->formatStateUsing(function ($state) {
                        // Handle both enum and string cases
                        $status = $state instanceof \App\Enum\InputStatus ? $state->value : $state;
                        return ucwords($status);
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('insurance.name')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('products.name')
                    ->label('Product')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('first_name')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('patient_phone')
                    ->label('Phone')
                    ->toggleable()
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('dob')
                    ->label('DOB')
                    ->date()
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('medicare_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('city')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('state')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('doctor_name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('patient_last_visit')
                    ->label('Last Visit')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => $isAdmin),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => $isAdmin),
                    ExportBulkAction::make()
                        ->visible(fn() => $isAdmin),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->deferLoading()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayables::route('/'),
            'edit' => Pages\EditPayable::route('/{record}/edit'),
        ];
    }
}
