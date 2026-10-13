<?php

namespace App\Bootstrap\Composition;

use App\Adapters\Persistence\EloquentBookRepository;
use App\Adapters\Persistence\EloquentCategoryReader;
use App\Adapters\Persistence\EloquentLoanRepository;
use App\Adapters\Persistence\EloquentMemberRepository;
use App\Adapters\Persistence\EloquentOpenLoansReader;
use App\Adapters\Persistence\EloquentReportReader;
use App\Adapters\Persistence\EloquentUserRepository;
use App\Adapters\Security\JwtTokenIssuer;
use App\Adapters\Security\LaravelPasswordHasher;
use App\Adapters\Storage\LocalCoverStorage;
use App\Adapters\System\SystemClock;
use App\Adapters\System\UuidIdGenerator;
use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryReader;
use App\Application\Ports\Clock;
use App\Application\Ports\CoverStorage;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Application\Ports\OpenLoansReader;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\ReportReader;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\UserRepository;
use App\Bootstrap\Console\BootstrapAdminCommand;
use Illuminate\Support\ServiceProvider;

// The ONLY place where a port meets its adapter (constitution Article III, architecture §1).
final class PortBindings extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    private const BINDINGS = [
        BookRepository::class => EloquentBookRepository::class,
        MemberRepository::class => EloquentMemberRepository::class,
        UserRepository::class => EloquentUserRepository::class,
        LoanRepository::class => EloquentLoanRepository::class,
        CategoryReader::class => EloquentCategoryReader::class,
        OpenLoansReader::class => EloquentOpenLoansReader::class,
        ReportReader::class => EloquentReportReader::class,
        PasswordHasher::class => LaravelPasswordHasher::class,
        TokenIssuer::class => JwtTokenIssuer::class,
        CoverStorage::class => LocalCoverStorage::class,
        Clock::class => SystemClock::class,
        IdGenerator::class => UuidIdGenerator::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $port => $adapter) {
            $this->app->bind($port, $adapter);
        }
    }

    public function boot(): void
    {
        $this->commands([BootstrapAdminCommand::class]);
    }
}
