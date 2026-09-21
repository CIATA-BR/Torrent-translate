<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationTokenMail;
use App\Models\Country;
use App\Models\Language;
use App\Models\Locale;
use App\Models\RegistrationToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function requestForm() { return view('auth.request-registration'); }

    public function sendToken(Request $request)
    {
        $data = $request->validate(['email' => ['required','email','max:255']]);
        $plain = Str::random(64);
        RegistrationToken::where('email',$data['email'])->whereNull('used_at')->delete();
        RegistrationToken::create([
            'email'=>$data['email'],
            'token_hash'=>hash('sha256',$plain),
            'expires_at'=>now()->addMinutes(30),
        ]);
        Mail::to($data['email'])->send(new RegistrationTokenMail(route('register.complete',['token'=>$plain])));
        return back()->with('status','Enviamos um link de confirmação para o e-mail informado.');
    }

    public function completeForm(string $token)
    {
        $record=$this->validToken($token);
        abort_unless($record,410,'Link inválido ou expirado.');
        return view('auth.complete-registration',[
            'token'=>$token,
            'email'=>$record->email,
            'countries'=>Country::orderBy('name')->get(),
            'languages'=>Language::orderBy('name')->get(),
        ]);
    }

    public function complete(Request $request,string $token)
    {
        $record=$this->validToken($token);
        abort_unless($record,410,'Link inválido ou expirado.');
        $data=$request->validate([
            'full_name'=>['required','string','max:255'],
            'country_id'=>['required','exists:countries,id'],
            'language_id'=>['required','exists:languages,id'],
            'password'=>['required','confirmed','min:12'],
        ]);
        abort_if(User::where('email',$record->email)->exists(),422,'Este e-mail já está cadastrado.');

        $country=Country::findOrFail($data['country_id']);
        $language=Language::findOrFail($data['language_id']);
        $localeCode=strtolower($language->code).'-'.strtoupper($country->iso2);

        $locale=Locale::firstOrCreate(['code'=>$localeCode],[
            'country_id'=>$country->id,
            'language_id'=>$language->id,
            'name'=>$language->name.' — '.$country->name,
            'active'=>true,
        ]);

        $user=User::create([
            'name'=>$data['full_name'],
            'full_name'=>$data['full_name'],
            'email'=>$record->email,
            'email_verified_at'=>now(),
            'locale_id'=>$locale->id,
            'password'=>Hash::make($data['password']),
        ]);

        $record->update(['used_at'=>now()]);
        Auth::login($user);
        return redirect()->route('translations.index')->with('status','Cadastro concluído.');
    }

    private function validToken(string $plain): ?RegistrationToken
    {
        return RegistrationToken::where('token_hash',hash('sha256',$plain))
            ->whereNull('used_at')->where('expires_at','>',now())->first();
    }
}
