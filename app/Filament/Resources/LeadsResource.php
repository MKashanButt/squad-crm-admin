<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadsResource\Pages;
use App\Models\Leads;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class LeadsResource extends Resource
{
    protected static ?string $model = Leads::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'returned' => 'Returned',
                        'billable' => 'Billable',
                        'paid' => 'Paid',
                    ])
                    ->visible(fn(): bool => Auth::user()->hasRole('admin'))
                    ->required(),
                Forms\Components\Select::make('insurance_id')
                    ->relationship('insurance', 'name')
                    ->required(),
                Forms\Components\Select::make('products_id')
                    ->relationship('products', 'name')
                    ->required(),
                Forms\Components\TextInput::make('patient_phone')
                    ->tel()
                    ->unique()
                    ->validationMessages([
                        'unique' => 'The number is already present'
                    ])
                    ->required()
                    ->maxLength(15),
                Forms\Components\TextInput::make('secondary_phone')
                    ->tel()
                    ->maxLength(15)
                    ->default(null),
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(15),
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->maxLength(15),
                Forms\Components\DatePicker::make('dob')
                    ->required(),
                Forms\Components\TextInput::make('medicare_id')
                    ->unique()
                    ->validationMessages([
                        'unique' => 'The Medicare Id is already present'
                    ])
                    ->required()
                    ->maxLength(15),
                Forms\Components\Textarea::make('address')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('city')
                    ->required()
                    ->maxLength(15),
                Forms\Components\TextInput::make('state')
                    ->required()
                    ->maxLength(15),
                Forms\Components\TextInput::make('zip')
                    ->required()
                    ->maxLength(15),
                Forms\Components\Textarea::make('product_specs')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('doctor_name')
                    ->required()
                    ->maxLength(30),
                Forms\Components\TextInput::make('patient_last_visit')
                    ->required()
                    ->maxLength(20),
                Forms\Components\Textarea::make('doctor_address')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('doctor_phone')
                    ->tel()
                    ->required()
                    ->maxLength(15),
                Forms\Components\TextInput::make('doctor_fax')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('doctor_npi')
                    ->required()
                    ->maxLength(50),
                Forms\Components\Textarea::make('recording_link')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('comments')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id())
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($user, $isAdmin) {
                $query->where('status', '!=', 'billable')
                    ->where('status', '!=', 'paid')
                    ->orderBy('id', 'desc');

                // Apply team filter if it exists in request
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
                            fn($q) => $q->where('name', 'like', "%{$search}%")
                        )
                    ),
                $isAdmin
                    ? Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'returned' => 'Returned',
                        'billable' => 'Billable',
                        'paid' => 'Paid',
                    ])
                    ->default('new')
                    ->extraAttributes(['class' => 'width-full'])
                    ->searchable()
                    ->disabled(fn() => !$isAdmin)
                    : Tables\Columns\TextColumn::make('status')
                    ->badge('primary')
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
                Tables\Columns\TextColumn::make('address')
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
                Tables\Columns\TextColumn::make('doctor_address')
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
                    ->searchable()
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
                    }),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_at')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_at'],
                                fn(Builder $query, $data) => $query->where('created_at', $data)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(): bool => $isAdmin)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => $isAdmin),
                    ExportBulkAction::make()
                        ->visible(fn(): bool => $isAdmin),
                ]),
                BulkAction::make('updateStatus')
                    ->form(function ($records) {
                        return [
                            Select::make('status')
                                ->label('Update Status')
                                ->options([
                                    'denied' => 'Denied',
                                    'error' => 'Error',
                                    'payable' => 'Payable',
                                    'approved' => 'Approved',
                                    'wrong doc' => 'Wrong doc',
                                    'paid' => 'Paid',
                                ]),
                        ];
                    })
                    ->action(function ($records, array $data) use ($table) {
                        $newStatus = $data['status'];

                        foreach ($records as $record) {
                            $record->update(['status' => $newStatus]);
                        }

                        Notification::make()
                            ->success()
                            ->title('Status Updated')
                            ->body('Status updated successfully for ' . count($records) . ' records.')
                            ->send();
                    })
                    ->visible(fn(): bool => $isAdmin),
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
        $user = auth()->user();
        return $user->hasRole('agent'); // Only allow admins to create
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLeads::route('/create'),
            'edit' => Pages\EditLeads::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user']); // Eager load user relationship
    }
}
