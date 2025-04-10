<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'hr' => 'HR',
                        'manager' => 'Manager',
                        'agent' => 'Agent',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('name', '!=', 'admin');
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->formatStateUsing(fn($record) => ucwords($record->role))
                    ->sortable(),
                Tables\Columns\TextColumn::make('leads_count')
                    ->label('Total Leads')
                    ->numeric()
                    ->sortable()
                    ->description(function (User $user): string {
                        return $user->leads->first()
                            ? "Last: " . $user->leads->first()->created_at->diffForHumans()
                            : "No leads";
                    })
                    ->color(function (User $user): string {
                        return match (true) {
                            $user->leads_count > 20 => 'success',
                            $user->leads_count > 5 => 'warning',
                            default => 'danger',
                        };
                    }),
                Tables\Columns\TextColumn::make('leads_count')
                    ->label('Total Leads')
                    ->state(function (User $user): int {
                        return $user->leads_count; // From withCount
                    })
                    ->numeric()
                    ->sortable()
                    ->description(fn(User $user): string => "Last lead: " .
                        ($user->leads->last()?->created_at->diffForHumans() ?? 'Never'))
                    ->color(function (User $user): string {
                        return match (true) {
                            $user->leads_count > 20 => 'success',
                            $user->leads_count > 5 => 'warning',
                            default => 'danger',
                        };
                    }),
                Tables\Columns\TextColumn::make('billed_leads_count')
                    ->label('Billed Leads')
                    ->numeric()
                    ->sortable()
                    ->color('success')
                    ->description(function (User $user): string {
                        $lastBilled = $user->billedLeads()->latest()->first();
                        return $lastBilled
                            ? "Last billed: " . $lastBilled->created_at->diffForHumans()
                            : "No billed leads";
                    }),
                Tables\Columns\TextColumn::make('return_leads_count')
                    ->label('Returned Leads')
                    ->numeric()
                    ->sortable()
                    ->color('danger')
                    ->weight('bold')
                    ->description(function (User $user): string {
                        $returnLeads = $user->returnLeads()->latest()->first();
                        return $returnLeads
                            ? "Last returned: " . $returnLeads->created_at->diffForHumans()
                            : "No Returned leads";
                    }),
                Tables\Columns\TextColumn::make('commission')
                    ->label('Commission (PKR)')
                    ->numeric()
                    ->sortable()
                    ->color(function (User $user): string {
                        $commission = ($user->billed_leads_count * 1000) - ($user->return_leads_count * 1000);
                        return $commission >= 0 ? 'primary' : 'danger'; // Red if negative
                    })
                    ->weight('bold')
                    ->state(function (User $user): string {
                        $billedAmount = $user->billed_leads_count * 1000;
                        $returnDeductions = $user->return_leads_count * 1000;
                        $netCommission = $billedAmount - $returnDeductions;
                        return number_format($netCommission);
                    })
                    ->description(function (User $user): string {
                        $billedCount = $user->billed_leads_count;
                        $returnCount = $user->return_leads_count;
                        $totalCommission = ($billedCount * 1000) - ($returnCount * 1000);

                        return sprintf(
                            "%d billed × 1000 PKR = %s PKR\n%d returned × -1000 PKR = - %s PKR",
                            $billedCount,
                            number_format($billedCount * 1000),
                            $returnCount,
                            number_format($returnCount * 1000),
                            number_format($totalCommission)
                        );
                    }),

            ])
            ->filters([
                //
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
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin'); // or your admin check logic
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'leads',
                'billedLeads as billed_leads_count',
                'returnLeads as return_leads_count',
                'leads as paid_leads_count' => function ($query) {
                    $query->where('status', 'paid');
                }
            ]);
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
