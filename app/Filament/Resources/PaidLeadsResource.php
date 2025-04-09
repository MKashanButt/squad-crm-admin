<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaidLeadsResource\Pages;
use App\Filament\Widgets\PaidLeadsAmountWidget;
use App\Models\Leads;
use App\Models\PaidLeads;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PaidLeadsResource extends Resource
{
    protected static ?string $model = PaidLeads::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'billable' => 'Billable',
                        'paid' => 'Paid',
                    ])
                    ->required(),
                Forms\Components\Select::make('insurance_id')
                    ->relationship('insurance', 'insurance')
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('status', 'Paid')
                    ->orderBy('id', 'desc');
            })
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->copyable()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('facility_name')
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
                Tables\Columns\TextColumn::make('calculated_amount')
                    ->label('Amount (PKR)')
                    ->getStateUsing(function (PaidLeads $record): string {
                        return number_format(1000) . ' PKR'; // Each lead = 1000 PKR
                    })
                    ->html()
                    ->alignRight()
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn(User $user): bool => $user->isAgent())
                    ->url(fn(PaidLeads $record): string => static::getUrl('edit', ['record' => $record]))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBulkAction::make()
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

    public static function canEdit(Model $record): bool
    {
        return false;
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaidLeads::route('/'),
            'create' => Pages\CreatePaidLeads::route('/create'),
            'edit' => Pages\EditPaidLeads::route('/{record}/edit'),
        ];
    }

    protected static function getHeaderWidgets(): array
    {
        return [
            PaidLeadsAmountWidget::class,
        ];
    }
}
