<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadsResource\Pages;
use App\Filament\Resources\LeadsResource\RelationManagers;
use App\Models\Leads;
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
use Laravel\Prompts\SearchPrompt;

class LeadsResource extends Resource
{
    protected static ?string $model = Leads::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'Denied' => 'Denied',
                        'Error' => 'Error',
                        'Payable' => 'Payable',
                        'Approved' => 'Approved',
                        'Wrong Doc' => 'Wrong Doc',
                        'Paid' => 'Paid',
                        'Awaiting' => 'Awaiting'
                    ])
                    ->required(),
                Forms\Components\Select::make('transfer_status')
                    ->options([
                        'Transferred' => 'Transferred',
                        'Not transferred' => 'Not transferred',
                        'Awaiting' => 'Awaiting'
                    ])
                    ->required(),
                Forms\Components\Select::make('center_code_id')
                    ->relationship('centerCode', 'code')
                    ->searchable()
                    ->preload()
                    ->noSearchResultsMessage('No Center Found')
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
                $query->where('status', '!=', 'payable')
                    ->orderBy('id', 'desc');
                $query->where('status', '!=', 'paid')
                    ->orderBy('id', 'desc');
            })
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transfer_status')
                    ->badge('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('centerCode.code')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('insurance.insurance')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('products.products')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('patient_phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('secondary_phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dob')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('medicare_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->searchable(),
                Tables\Columns\TextColumn::make('zip')
                    ->searchable(),
                Tables\Columns\TextColumn::make('doctor_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('facility_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('patient_last_visit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('doctor_phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('doctor_fax')
                    ->searchable(),
                Tables\Columns\TextColumn::make('doctor_npi')
                    ->searchable()
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'denied' => 'Denied',
                        'error' => 'Error',
                        'payable' => 'Payable',
                        'approved' => 'Approved',
                        'wrong doc' => 'Wrong doc',
                        'paid' => 'Paid',
                    ]),
                SelectFilter::make('center_code_id')
                    ->relationship('centerCode', 'code')
                    ->searchable()
                    ->preload()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLeads::route('/create'),
            'edit' => Pages\EditLeads::route('/{record}/edit'),
        ];
    }
}
