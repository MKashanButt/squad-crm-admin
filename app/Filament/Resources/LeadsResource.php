<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadsResource\Pages;
use App\Filament\Resources\LeadsResource\RelationManagers;
use App\Models\Leads;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Collection;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Laravel\Prompts\SearchPrompt;
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
                Forms\Components\TextInput::make('team'),
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
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('team')
                    ->extraAttributes([
                        'class' => 'width-full',
                    ])
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('status')
                    ->extraAttributes([
                        'class' => 'width-full',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('transfer_status')
                    ->badge('status')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('centerCode.code')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('insurance.insurance')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('products.products')
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
                Tables\Columns\TextColumn::make('facility_name')
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
                    ->preload(),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_at')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_at'],
                                fn (Builder $query, $data) => $query->where('created_at', $data)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
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
                    }),
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
