<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnResource\Pages;
use App\Models\Leads;
use App\Models\ReturnLeads;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReturnResource extends Resource
{
    protected static ?string $model = ReturnLeads::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($user, $isAdmin) {
                $query->where('status', 'returned')
                    ->orderBy('id', 'desc');

                if (request()->has('filters.team')) {
                    $query->whereHas('user', function ($q) {
                        $q->where('team', request('filters.team'));
                    });
                }

                // Restrict to user's leads if not admin
                if (!$isAdmin) {
                    if ($user->hasRole('manager')) {
                        // For managers, show all leads from their team
                        $query->whereHas('user', function ($q) use ($user) {
                            $q->where('team', strtolower($user->name)); // team = username (lowercase)
                        });
                    } else {
                        // For regular agents, show only their own leads
                        $query->where('user_id', $user->id);
                    }
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Agent Name')
                    ->formatStateUsing(function ($state) {
                        return $state ? ucwords($state) : 'N/A';
                    })
                    ->extraAttributes(['class' => 'width-full'])
                    ->visible(fn(): bool => auth()->user()->hasRole('admin'))
                    ->sortable()
                    ->searchable(
                        query: fn(Builder $query, string $search) => $query->whereHas(
                            'user',
                            fn($q) => $q->where('name', 'like', "%{$search}%"),
                Tables\Columns\TextColumn::make('status')
                    ->badge('status')
                    ->default(fn($record) => ucwords($record->status))
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('insurance.name')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('products.name')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('patient_phone')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('secondary_phone')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('dob')
                    ->copyable()
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('medicare_id')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('zip')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('doctor_name')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('patient_last_visit')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('doctor_phone')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('doctor_fax')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('doctor_npi')
                    ->copyable()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('team')
                    ->relationship('user', 'team')
                    ->searchable()
                    ->preload()
                    ->visible(fn(): bool => auth()->user()->hasRole('admin'))
                    ->label('Team')
                    ->options(function () {
                        return User::select('team')
                            ->whereNotNull('team')
                            ->whereRaw('UCWORDS(team) != ?', ['alpha'])
                            ->groupBy('team')
                            ->orderBy('team')
                            ->get()
                            ->pluck('team')
                            ->reject(fn($team) => strtolower($team) === 'alpha') // Case-insensitive rejection
                            ->mapWithKeys(fn($team) => [
                                $team => ucwords(strtolower($team))
                            ])
                            ->toArray();
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn(User $user): bool => $user->isAgent())
                    ->url(fn(Leads $record): string => static::getUrl('edit', ['record' => $record]))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => $isAdmin),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return !auth()->user()->hasRole('hr'); // or your admin check logic
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturns::route('/'),
            'create' => Pages\CreateReturn::route('/create'),
            'edit' => Pages\EditReturn::route('/{record}/edit'),
        ];
    }
}
