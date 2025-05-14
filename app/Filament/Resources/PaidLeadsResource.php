<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaidLeadsResource\Pages;
use App\Filament\Widgets\PaidLeadsAmountWidget;
use App\Models\PaidLeads;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PaidLeadsResource extends Resource
{
    protected static ?string $model = PaidLeads::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 3;
    protected static ?string $label = 'Paid';
    protected static ?string $pluralLabel = 'Paid';
    protected static ?string $navigationGroup = 'Lead Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'billable' => 'Billable',
                                'paid' => 'Paid',
                            ])
                            ->required(),
                    ]),

                Forms\Components\Section::make('Lead Details')
                    ->schema([
                        Forms\Components\Select::make('insurance_id')
                            ->relationship('insurance', 'insurance')
                            ->required(),

                        Forms\Components\Select::make('products_id')
                            ->relationship('products', 'products')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Patient Information')
                    ->schema([
                        Forms\Components\TextInput::make('patient_phone')
                            ->tel()
                            ->required()
                            ->maxLength(15),

                        Forms\Components\TextInput::make('secondary_phone')
                            ->tel()
                            ->maxLength(15),

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
                    ])->columns(2),

                Forms\Components\Section::make('Address')
                    ->schema([
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
                    ])->columns(3),

                Forms\Components\Section::make('Medical Information')
                    ->schema([
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
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('product_specs')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('recording_link')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('comments')
                            ->required()
                            ->columnSpanFull(),
                    ])
            ]);
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
            ->where('status', 'Paid')
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
                        'payable' => 'Payable',
                        'bad lead' => 'Bad Lead',
                        'returned' => 'Returned',
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
                    ->label('Insurance')
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
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn() => Auth::user()->isAgent()),
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaidLeads::route('/'),
        ];
    }

    protected static function getHeaderWidgets(): array
    {
        return [
            PaidLeadsAmountWidget::class,
        ];
    }
}
