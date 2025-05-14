<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $modelLabel = 'Member';
    protected static ?string $pluralModelLabel = 'Members';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => Hash::make($state)),

                        Forms\Components\Select::make('role') // Changed from 'roles' to 'role'
                            ->options([
                                'admin' => 'Admin',
                                'hr' => 'HR',
                                'qa' => 'QA',
                                'manager' => 'Manager',
                                'agent' => 'Agent',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('team')
                            ->maxLength(50)
                            ->helperText('Team/Department name'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('name', '!=', 'admin'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('role') // Changed from 'roles.name' to 'role'
                    ->badge()
                    ->formatStateUsing(fn($state) => ucwords($state))
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'hr' => 'success',
                        default => 'primary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('leads_count')
                    ->label('Total Leads')
                    ->numeric()
                    ->sortable()
                    ->description(
                        fn(User $user): string =>
                        $user->leads->last()?->created_at->diffForHumans() ?? 'No leads'
                    )
                    ->color(fn(User $user): string => match (true) {
                        $user->leads_count > 20 => 'success',
                        $user->leads_count > 5 => 'warning',
                        default => 'danger',
                    })
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Leads')
                    ]),

                Tables\Columns\TextColumn::make('billed_leads_count')
                    ->label('Billed')
                    ->numeric()
                    ->sortable()
                    ->color('success')
                    ->description(
                        fn(User $user): string =>
                        $user->billedLeads()->latest()->first()?->created_at->diffForHumans() ?? 'Never'
                    )
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Payable')
                    ]),

                Tables\Columns\TextColumn::make('return_leads_count')
                    ->label('Returns')
                    ->numeric()
                    ->sortable()
                    ->color('danger')
                    ->description(
                        fn(User $user): string =>
                        $user->returnLeads()->latest()->first()?->created_at->diffForHumans() ?? 'None'
                    )
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Returns')
                    ]),

                Tables\Columns\TextColumn::make('commission_amount') // Now using the computed value
                    ->label('Commission (PKR)')
                    ->numeric()
                    ->sortable()
                    ->color(fn($state): string => $state >= 0 ? 'primary' : 'danger')
                    ->formatStateUsing(fn($state): string => number_format($state))
                    ->description(function (User $user) {
                        $billed = $user->billed_leads_count;
                        $returned = $user->return_leads_count;
                        return sprintf("%d × 1000 - %d × 1000", $billed, $returned);
                    })
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Commissions')
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role') // Changed from 'roles' to 'role'
                    ->options([
                        'admin' => 'Admin',
                        'hr' => 'HR',
                        'manager' => 'Manager',
                        'agent' => 'Agent',
                        'qa' => 'QA',
                    ]),

                Tables\Filters\Filter::make('has_leads')
                    ->label('Has Leads')
                    ->query(fn(Builder $query): Builder => $query->whereHas('leads')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Edit User'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $records->each(function ($user) {
                                if ($user->leads()->exists()) {
                                    throw new \Exception("Cannot delete users with assigned leads");
                                }
                            });
                        }),
                ])->hidden(!auth()->user()->hasRole('admin')),
            ])
            ->defaultSort('leads_count', 'desc')
            ->persistSortInSession()
            ->deferLoading();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'leads',
                'billedLeads as billed_leads_count',
                'returnLeads as return_leads_count',
                'leads as paid_leads_count' => fn($query) => $query->where('status', 'paid')
            ])
            ->addSelect([
                // Calculate commission directly in SQL for better performance
                DB::raw('(SELECT COUNT(*) FROM form_inputs WHERE form_inputs.user_id = users.id AND status = "payable") * 1000 - 
                 (SELECT COUNT(*) FROM form_inputs WHERE form_inputs.user_id = users.id AND status = "returned") * 1000 AS commission_amount')
            ]);;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->hasRole('hr');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
