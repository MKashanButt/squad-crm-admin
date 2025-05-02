<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Products;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ProductResource extends Resource
{
    protected static ?string $model = Products::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'References';
    protected static ?string $modelLabel = 'Product';
    protected static ?string $pluralModelLabel = 'Products';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'This product name already exists'
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('leads_count')
                    ->label('Associated Leads')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Edit Product'),

                Tables\Actions\Action::make('viewLeads')
                    ->label('View Leads')
                    ->url(fn(Products $record) => LeadsResource::getUrl('index', [
                        'tableFilters[products_id][value]' => $record->id
                    ]))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('View associated leads'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $hasLeads = $records->contains(fn($record) => $record->leads()->exists());
                            if ($hasLeads) {
                                throw new \Exception('Cannot delete products with associated leads');
                            }
                        }),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Product'),
            ])
            ->persistSortInSession()
            ->deferLoading();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('formInputs');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
