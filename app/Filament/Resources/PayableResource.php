<?php

namespace App\Filament\Resources;

use App\Enum\InputStatus;
use App\Filament\Resources\PayableResource\Pages;
use App\Filament\Resources\LeadsResource\Pages as LeadPages;
use App\Filament\Resources\PayableResource\RelationManagers;
use App\Models\Payable;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PayableResource extends Resource
{
    protected static ?string $model = Payable::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'Commission Payable';
    protected static ?string $pluralLabel = 'Commission Payable';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('center_code_id')
                    ->relationship('centerCode', 'code')
                    ->searchable()
                    ->preload()
                    ->noSearchResultsMessage('No Center Found')
                    ->required(),
                Forms\Components\Select::make('insurance_id')
                    ->relationship('insurance', 'name')
                    ->required(),
                Forms\Components\Select::make('products_id')
                    ->relationship('products', 'products')
                    ->required(),
                Forms\Components\TextInput::make('patient_phone')
                    ->tel()
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
                Forms\Components\TextInput::make('facility_name')
                    ->required()
                    ->maxLength(20),
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
                Forms\Components\Select::make('users_id')
                    ->relationship('users', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($user, $isAdmin) {
                $query->where('status', 'billable')
                    ->orderBy('id', 'desc');
                if (request()->has('filters.team')) {
                    $query->whereHas('user', function ($q) {  // Must match the relationship name
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
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
                        'billable' => 'Billable',
                        'paid' => 'Paid',
                    ])
                    ->default('new')
                    ->extraAttributes(['class' => 'width-full'])
                    ->searchable()
                    ->disabled(fn() => !$isAdmin)
                    : Tables\Columns\TextColumn::make('status')
                    ->badge('status')
                    ->default(fn($record) => ucwords($record->status))
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
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => Auth::user()->hasRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => $isAdmin),
                ]),
                ExportBulkAction::make()
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
        return false;
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayables::route('/'),
            'create' => LeadPages\CreateLeads::route('/create'),
            'edit' => LeadPages\EditLeads::route('/{record}/edit'),
        ];
    }
}
