<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{StoreSetting, StoreBanner};
use Illuminate\Http\Request;
class StoreSettingController extends Controller {
    public function edit() {
        return view('admin.settings.index',[
            'settings'=>StoreSetting::current(),
            'banners'=>StoreBanner::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }
    public function update(Request $request) {
        $data = $request->validate([
            'name'=>'required|string|max:100','description'=>'nullable|string|max:1000',
            'logo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'promptpay_qr'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'truemoney_qr'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'promptpay_instructions'=>'nullable|string|max:2000','truemoney_instructions'=>'nullable|string|max:2000',
        ]);
        foreach (['logo','promptpay_qr','truemoney_qr'] as $field) {
            if ($request->hasFile($field)) $data[$field] = $request->file($field)->store('store-settings','public');
            else unset($data[$field]);
        }
        $settings = StoreSetting::firstOrNew(['id'=>1]);
        $settings->id = 1;
        $settings->fill($data)->save();
        return redirect()->route('admin.settings.edit')->with('success','บันทึกข้อมูลร้านและ QR แล้ว');
    }
    public function storeBanner(Request $request) { return $this->saveBanner($request,new StoreBanner()); }
    public function updateContact(Request $request) {
        $urlRules = ['nullable','string','max:2048','url',function ($attribute,$value,$fail) {
            if (strtolower(parse_url($value,PHP_URL_SCHEME) ?? '') !== 'https'
                || parse_url($value,PHP_URL_USER) !== null
                || str_contains($value,chr(92))) $fail('กรุณาใช้ลิงก์ https:// ที่ไม่มีชื่อผู้ใช้หรือรหัสผ่านใน URL');
        }];
        $data = $request->validate([
            'facebook_url'=>$urlRules,'line_url'=>$urlRules,'discord_url'=>$urlRules,
            'opening_hours'=>'nullable|string|max:300',
            'floating_contact_enabled'=>'required|boolean',
            'announcement_enabled'=>'required|boolean',
            'announcement'=>'nullable|required_if:announcement_enabled,1|string|max:1000',
        ]);
        $settings = StoreSetting::firstOrNew(['id'=>1]);
        $settings->id = 1;
        $settings->fill($data)->save();
        return redirect()->route('admin.settings.edit')->with('success','บันทึกช่องทางติดต่อและประกาศร้านแล้ว');
    }
    public function updateBanner(Request $request, StoreBanner $banner) { return $this->saveBanner($request,$banner); }
    private function saveBanner(Request $request, StoreBanner $banner) {
        $data = $request->validate([
            'title'=>'required|string|max:150',
            'image'=>[$banner->exists ? 'nullable' : 'required','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'sort_order'=>'required|integer|min:0|max:10000','is_active'=>'nullable|boolean',
            'link'=>['nullable','string','max:2048',function ($attribute,$value,$fail) {
                $local = str_starts_with($value,'/') && !str_starts_with($value,'//');
                $remote = filter_var($value,FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value,PHP_URL_SCHEME) ?? ''),['http','https'],true);
                if ((!$local && !$remote) || preg_match('/[\\\\\s]/',$value) || preg_match('/%(?:0[ad]|5c)/i',$value)) $fail('ลิงก์ต้องเป็น /categories หรือ URL ที่ขึ้นต้นด้วย http:// หรือ https://');
            }],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        if ($request->hasFile('image')) $data['image'] = $request->file('image')->store('store-banners','public');
        else unset($data['image']);
        $banner->fill($data)->save();
        return redirect()->route('admin.settings.edit')->with('success','บันทึกแบนเนอร์แล้ว');
    }
}
