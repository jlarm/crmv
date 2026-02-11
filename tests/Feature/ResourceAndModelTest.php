<?php

declare(strict_types=1);

use App\Http\Resources\DealershipResource;
use App\Http\Resources\DealershipShowResource;
use App\Http\Resources\UserResource;
use App\Models\Attachable;
use App\Models\Company;
use App\Models\Contact;
use App\Models\DealerEmail;
use App\Models\DealerEmailTemplate;
use App\Models\Dealership;
use App\Models\EmailTrackingEvent;
use App\Models\Organization;
use App\Models\PdfAttachment;
use App\Models\Progress;
use App\Models\ProgressCategory;
use App\Models\Reminder;
use App\Models\SentEmail;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

test('dealership resource maps status and rating variants', function () {
    $resource = new DealershipResource((object) [
        'id' => 1,
        'name' => 'A',
        'city' => 'Austin',
        'state' => 'TX',
        'status' => 'active',
        'rating' => 'warm',
    ]);

    $array = $resource->toArray(new Request());

    expect($array['statusLabel'])->toBe('Active');
    expect($array['statusVariant'])->toBe('default');
    expect($array['ratingLabel'])->toBe('Warm');
    expect($array['ratingVariant'])->toBe('outline');
});

test('dealership show resource transforms loaded relations', function () {
    $organization = Organization::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => 'Org',
    ]);
    $user = User::factory()->create(['current_organization_id' => $organization->id]);
    $user->organizations()->attach($organization->id);

    $company = Company::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'name' => 'Company',
        'status' => 'active',
        'rating' => 'warm',
        'type' => 'general',
    ]);

    $store = Store::query()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'name' => 'Store',
    ]);
    $contact = Contact::query()->create([
        'company_id' => $company->id,
        'name' => 'Contact',
        'primary_contact' => true,
    ]);
    Progress::query()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'contact_id' => $contact->id,
        'details' => 'Done',
    ]);
    $company->users()->attach($user->id);

    $company->load(['stores', 'contacts', 'progresses.contact', 'users']);

    $array = (new DealershipShowResource($company))->toArray(new Request());

    expect($array['stores'][0]['id'])->toBe($store->id);
    expect($array['contacts'][0]['primaryContact'])->toBeTrue();
    expect($array['progresses'][0]['contact']['id'])->toBe($contact->id);
    $users = $array['users'] instanceof Illuminate\Http\Resources\Json\AnonymousResourceCollection
        ? $array['users']->resolve()
        : $array['users'];
    $firstUser = array_is_list($users) ? ($users[0] ?? null) : (($users['data'][0] ?? null));
    expect($firstUser['id'] ?? null)->toBe($user->id);
});

test('user resource maps id and name', function () {
    $array = (new UserResource((object) ['id' => 5, 'name' => 'Tester']))
        ->toArray(new Request());

    expect($array)->toBe([
        'id' => 5,
        'name' => 'Tester',
    ]);
});

test('model relation methods return expected relation types', function () {
    expect((new Attachable())->pdfAttachment())->toBeInstanceOf(BelongsTo::class);
    expect((new Attachable())->attachable())->toBeInstanceOf(MorphTo::class);

    expect((new Company())->user())->toBeInstanceOf(BelongsTo::class);
    expect((new Company())->organization())->toBeInstanceOf(BelongsTo::class);
    expect((new Company())->users())->toBeInstanceOf(BelongsToMany::class);
    expect((new Company())->contacts())->toBeInstanceOf(HasMany::class);
    expect((new Company())->stores())->toBeInstanceOf(HasMany::class);
    expect((new Company())->progresses())->toBeInstanceOf(HasMany::class);

    expect((new Contact())->company())->toBeInstanceOf(BelongsTo::class);
    expect((new Contact())->progresses())->toBeInstanceOf(HasMany::class);
    expect((new Contact())->tags())->toBeInstanceOf(BelongsToMany::class);

    expect((new DealerEmail())->user())->toBeInstanceOf(BelongsTo::class);
    expect((new DealerEmail())->company())->toBeInstanceOf(BelongsTo::class);
    expect((new DealerEmail())->dealerEmailTemplate())->toBeInstanceOf(BelongsTo::class);
    expect((new DealerEmail())->pdfAttachments())->toBeInstanceOf(MorphToMany::class);

    expect((new DealerEmailTemplate())->dealerEmails())->toBeInstanceOf(HasMany::class);

    expect((new Dealership())->user())->toBeInstanceOf(BelongsTo::class);
    expect((new Dealership())->organization())->toBeInstanceOf(BelongsTo::class);
    expect((new Dealership())->users())->toBeInstanceOf(BelongsToMany::class);

    expect((new EmailTrackingEvent())->sentEmail())->toBeInstanceOf(BelongsTo::class);
    expect((new Organization())->users())->toBeInstanceOf(BelongsToMany::class);

    expect((new PdfAttachment())->attachables())->toBeInstanceOf(HasMany::class);
    expect((new PdfAttachment())->dealerEmails())->toBeInstanceOf(MorphToMany::class);

    expect((new Progress())->user())->toBeInstanceOf(BelongsTo::class);
    expect((new Progress())->company())->toBeInstanceOf(BelongsTo::class);
    expect((new Progress())->contact())->toBeInstanceOf(BelongsTo::class);
    expect((new Progress())->progressCategory())->toBeInstanceOf(BelongsTo::class);

    expect((new ProgressCategory())->progresses())->toBeInstanceOf(HasMany::class);
    expect((new Reminder())->user())->toBeInstanceOf(BelongsTo::class);

    expect((new SentEmail())->user())->toBeInstanceOf(BelongsTo::class);
    expect((new SentEmail())->company())->toBeInstanceOf(BelongsTo::class);
    expect((new SentEmail())->emailTrackingEvents())->toBeInstanceOf(HasMany::class);

    expect((new Store())->user())->toBeInstanceOf(BelongsTo::class);
    expect((new Store())->company())->toBeInstanceOf(BelongsTo::class);
    expect((new Tag())->contacts())->toBeInstanceOf(BelongsToMany::class);
});

test('user initials returns first letters from first two words', function () {
    $user = new User();
    $user->name = 'Jane Doe Smith';

    expect($user->initials())->toBe('JD');
});
