<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\admindb;
use App\announce;
use App\blogdb;
use App\colorproduct;
use App\Contact;
use App\email;
use App\gallerydb;
use App\itemproduk;
use App\kategori;
use App\Mail\SendMessage;
use App\partner;
use App\productsdb;
use App\video;

class DashboardController extends Controller
{
    public function loginshow()
    {
        return view('auth.login');
    }
    public function loginproses(Request $request)
    {
        $username = $request->username;
        $password = $request->password;
        $check = DB::table('admindbs')
            ->where('username', $username)
            ->first();

        if ($check && HASH::check($password, $check->password) && $check->status == 'active') {
            $check->isLogin = 'login';
            $check = (array)$check;
            session($check);

            return redirect('/admin/dashboard');
        }
        return back()->with('gagal', 'Coba periksa kembali otoritas keanggotaanmu atau pertanyakan kepada kepala admin.');
        // dd($request->all());
    }

    // Content GET of Dashboard
    public function index()
    {
        $ann = DB::table('announces')
            ->where('status', '=', 'Active')
            ->first();
        return view('dashboard.index', ['ann' => $ann]);
    }
    // User Section
    public function showuser()
    {
        $getuser = DB::table('admindbs')
            ->where('admindbs.role', '!=', 'Developer')
            ->orderBy('admindbs.name', 'ASC')
            ->select('admindbs.*')
            ->get();
        return view('dashboard.user.show', ['getuser' => $getuser]);
    }
    public function prosesaaddnew(Request $request)
    {
        $user = new admindb();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->password = HASH::make($request->password);
        $user->unpassword = $request->verpassword;
        $user->role = $request->role;
        $user->status = 'active';
        $user->birthdate = '-';
        $user->save();

        return back()->with('selamat', 'Data user berhasil kamu tambahkan. Dan sudah bisa digunakan.');
    }
    public function trashuser($id, Request $request)
    {
        $user = admindb::find($id);
        // dd($user);
        if ($user) {
            if ($user->delete()) {
                DB::statement('ALTER TABLE admindbs AUTO_INCREMENT = ' . (count(admindb::all()) + 1) . ';');

                return back()->with('selamat', 'Data user berhasil dihapus.');
            }
        }
    }
    public function updateuser($id, Request $request)
    {
        $user = admindb::find($id);
        $user->name = $request->name;
        $user->username = $request->username;
        $user->password = HASH::make($request->password);
        $user->unpassword = $request->verpassword;
        $user->role = $request->role;
        $user->save();

        return back()->with('selamat', 'Data user berhasil diupdate');
    }
    // End User section

    // BLOG SECTION
    public function showblog()
    {
        $product = DB::table('productsdbs')
            ->orderBy('productsdbs.product_name', 'ASC')
            ->select('productsdbs.*')
            ->get();
        $blog = DB::table('blogdbs')
            ->orderby('blogdbs.created_at', 'DESC')
            ->select('blogdbs.*')
            ->get();
        return view('dashboard.blog.show', ['product' => $product, 'blog' => $blog]);
    }
    public function prosesaddblog(Request $request)
    {
        $blog = new blogdb();
        $blog->judul = $request->judul;
        $blog->kategori_produk = $request->kategori_produk;
        $blog->isi = $request->isi;
        if (!$request->hasFile('coverimg')) {
            $blog->save();
        } else {
            $lamp = $request->file('coverimg');
            $filename = time() . '.' . $lamp->getClientOriginalExtension();
            $request->file('coverimg')->move('media/blog/', $filename);
            $blog->coverimg = $filename;
            $blog->save();
        }
        return back()->with('selamat', 'Projek blog kamu sudah berhasil kamu tambahkan');
    }
    public function trashblog($id, Request $request)
    {
        $blog = blogdb::find($id);
        // dd($user);
        if ($blog) {
            if ($blog->delete()) {
                DB::statement('ALTER TABLE blogdbs AUTO_INCREMENT = ' . (count(blogdb::all()) + 1) . ';');

                return back()->with('selamat', 'Data Blog berhasil dihapus.');
            }
        }
    }
    public function updateblog($id, Request $request)
    {
        $blog = blogdb::find($id);
        $blog->judul = $request->judul;
        $blog->isi = $request->isi;
        $blog->save();

        return back()->with('selamat', 'Berhasil update data blog');
    }
    // END BLOG SECTION

    // GALLERY SECTION
    public function showgallery()
    {
        $product = DB::table('productsdbs')
            ->orderBy('productsdbs.product_name', 'ASC')
            ->select('productsdbs.*')
            ->get();
        $gal = DB::table('gallerydbs')
            ->orderBy('gallerydbs.created_at', 'DESC')
            ->select('gallerydbs.*')
            ->get();
        $galp = DB::table('gallerydbs')
            ->join('productsdbs', 'gallerydbs.product_id', '=', 'productsdbs.id')
            ->select('gallerydbs.*', 'productsdbs.product_name', 'productsdbs.description')
            ->orderBy('gallerydbs.created_at', 'DESC')
            ->get();
        $color = DB::table('colorproducts')
            ->orderBy('colorproducts.color_name', 'ASC')
            ->select('colorproducts.*')
            ->get();
        return view('dashboard.gallery.show', ['product' => $product, 'gal' => $gal, 'galp' => $galp, 'color' => $color]);
        // dd($galp);
    }
    public function prosesaddgallery(Request $request)
    {
        $gal = new gallerydb();
        $gal->judul_foto = $request->judul_foto;
        $gal->product_id = $request->product_id;
        $gal->color_id = $request->color_id;
        if (!$request->hasFile('img')) {
            $gal->save();
        } else {
            $lamp = $request->file('img');
            $filename = time() . '.' . $lamp->getClientOriginalExtension();
            $request->file('img')->move('media/gallery/', $filename);
            $gal->img = $filename;
            $gal->status = 'waiting';
            $gal->created_by = session()->get('username');
            $gal->updated_by = session()->get('username');
            $gal->save();
        }
        // dd($gal);
        return back()->with('selamat', 'Foto kamu berhasil ditambahkan didalam galeri!');
    }
    public function trashgallery($id)
    {
        $gal = gallerydb::find($id);
        // dd($user);
        if ($gal) {
            if ($gal->delete()) {
                DB::statement('ALTER TABLE gallerydbs AUTO_INCREMENT = ' . (count(gallerydb::all()) + 1) . ';');

                return back()->with('selamat', 'Data foto dalam galeri ini berhasil dihapus.');
            }
        }
    }
    public function verifygallery(Request $request, $id)
    {
        $gal = gallerydb::find($id);
        $gal->update($request->all());
        // dd($user);
        $gal->status = 'approved';
        $gal->approved_by = session()->get('username');
        $gal->updated_by = session()->get('username');
        $gal->save();
        return redirect()->back()->with('selamat', 'Data foto dalam galeri ini berhasil diverifikasi!');
    }
    public function declinedgallery(Request $request, $id)
    {
        $gal = gallerydb::find($id);
        $gal->update($request->all());
        // dd($user);
        $gal->status = 'declined';
        $gal->approved_by = session()->get('username');
        $gal->updated_by = session()->get('username');
        $gal->save();
        return redirect()->back()->with('selamat', 'Data foto dalam galeri ini diupdate menjadi status batal!');
    }
    // PRODUCTS SECTION
    public function showproducts()
    {
        $products = DB::table('productsdbs')
            ->orderBy('productsdbs.product_name', 'ASC')
            ->select('productsdbs.*')
            ->get();
        $productget = DB::table('productsdbs')
            ->orderBy('productsdbs.product_name', 'ASC')
            ->select('productsdbs.*')
            ->get();
        $kategori = DB::table('kategoris')
            ->join('productsdbs', 'kategoris.product_id', '=', 'productsdbs.id')
            ->orderBy('kategoris.nama_kategori', 'ASC')
            ->select('kategoris.*', 'productsdbs.*', 'kategoris.description as descriptionKat')
            ->get();
        $produk = DB::table('itemproduks')
            ->orderBy('itemproduks.nama_item', 'ASC')
            ->select('itemproduks.*')
            ->get();
        $itemproduk = DB::table('itemproduks')
            ->join('kategoris', 'itemproduks.kategori_id', '=', 'kategoris.id')
            ->orderBy('itemproduks.nama_item', 'ASC')
            ->select('kategoris.*', 'itemproduks.*', 'kategoris.description as descriptionItem')
            ->get();
        $color = DB::table('colorproducts')
            ->orderBy('colorproducts.created_at', 'DESC')
            ->select('colorproducts.*')
            ->get();
        $kategoriItem = kategori::all();
        $katalogItem = productsdb::all();
        return view('dashboard.products.show', ['products' => $products, 'kategori' => $kategori, 'produk' => $produk, 'productget' => $productget, 'itemproduk' => $itemproduk, 'kategoriItem' => $kategoriItem, 'katalogItem' => $katalogItem, 'color' => $color]);
    }
    // // // Kategori Section
    public function prosesaddkategori(Request $request)
    {
        $kategori = new kategori();
        $kategori->product_id = $request->product_id;
        $kategori->nama_kategori = $request->nama_kategori;
        $kategori->description = $request->description;
        // dd($request->all());
        $kategori->save();
        return redirect('/admin/products')->with('selamat', 'Data kategori produk berhasil diupdate');
    }
    // End Section
    // // // // // // // // // // // // // //

    // // // Item Section
    public function prosesadditem(Request $request)
    {
        $item = new itemproduk();
        $item->kategori_id = $request->kategori_id;
        $item->katalog_id = $request->katalog_id;
        $item->nama_item = $request->nama_produk;
        $item->description = $request->description;

        if (!$request->hasFile('fileimg')) {
            $item->save();
        } else {
            $lamp = $request->file('fileimg');
            $filename = time() . '.' . $lamp->getClientOriginalExtension();
            $request->file('fileimg')->move('media/product/item/', $filename);
            $item->fileimg = $filename;
            $item->save();
        }
        // dd($request->all());
        return redirect('/admin/products')->with('selamat', 'Data item produk berhasil ditambahkan');
    }
    public function prosesaddcolor(Request $request)
    {
        $color = new colorproduct();
        $color->color_name = $request->color_name;
        if (!$request->hasFile('file_color')) {
            $color->save();
        } else {
            $lamp = $request->file('file_color');
            $filename = time() . '.' . $lamp->getClientOriginalExtension();
            $request->file('file_color')->move('media/product/color/', $filename);
            $color->file_color = $filename;
            $color->created_by = session()->get('username');
            $color->updated_by = session()->get('username');
            $color->save();
        }
        return redirect('/admin/products')->with('selamat', 'Data warna produk berhasil ditambahkan');
        // dd($color);
    }
    public function trashcolor($id)
    {
        $color = colorproduct::find($id);
        // dd($user);
        if ($color) {
            if ($color->delete()) {
                DB::statement('ALTER TABLE colorproducts AUTO_INCREMENT = ' . (count(colorproduct::all()) + 1) . ';');

                return back()->with('selamat', 'Data Warna produk berhasil dihapus.');
            }
        }
    }

    // End Item Section
    public function prosesaddproduct(Request $request)
    {
        $product = new productsdb();
        $product->product_name = $request->product_name;
        $product->description = $request->description;
        $product->save();

        return back()->with('selamat', 'Berhasil menambahkan data produk baru anda.');
    }
    public function trashproduct($id)
    {
        $product = productsdb::find($id);
        // dd($user);
        if ($product) {
            if ($product->delete()) {
                DB::statement('ALTER TABLE productsdbs AUTO_INCREMENT = ' . (count(productsdb::all()) + 1) . ';');

                return back()->with('selamat', 'Data Produk berhasil dihapus.');
            }
        }
    }
    public function itemtrashproduct($id)
    {
        $product = itemproduk::find($id);
        // dd($user);
        if ($product) {
            if ($product->delete()) {
                DB::statement('ALTER TABLE itemproduks AUTO_INCREMENT = ' . (count(itemproduk::all()) + 1) . ';');

                return back()->with('selamat', 'Data Item Produk berhasil dihapus.');
            }
        }
    }
    public function updateproduct($id, Request $request)
    {
        $product = productsdb::find($id);
        $product->product_name = $request->product_name;
        $product->description = $request->description;
        $product->save();

        return back()->with('selamat', 'Data produk anda berhasil diupdate.');
    }
    // PRODUCT END SECTION
    // PRODUCT END SECTION

    // EMAILS SECTION
    public function showemails()
    {
        $email = DB::table('emails')
            ->orderBy('emails.created_at', 'DESC')
            ->select('emails.*')
            ->get();
        return view('dashboard.emails.show', ['email' => $email]);
    }

    // Email kirim
    public function kirimpesan(Request $request)
    {
        // dd($request->all());
        $name = $request->name;
        $email = new email();
        $email->name = $request->name;
        $email->email = $request->email;
        $email->nohp = $request->nohp;
        $email->message = $request->message;
        $email->type = $request->type;
        // dd($email);
        $email->save();
        // return back()->with('great', 'Halo ' . $name . ', kami telah menerima pesan kamu. Biasanya kami membalas dalam waktu 3x24 jam, dan kami akan segera membalas ke email anda maupun melalui nomor telepon yang sudah kamu input. Terima kasih ya.');
        return view('receivedemails', ['name' => $name]);
    }
    public function changestatus($id, Request $request)
    {
        $email = email::find($id);
        $email->status = 'read';
        $email->save();
        return back()->with('selamat', 'Email tersebut sudah berhasil diubah ke status sudah dibaca.');
    }

    // Announcement Section
    public function showannounce()
    {
        $ann = announce::all();
        return view('dashboard.announce.show', ['ann' => $ann]);
    }
    public function addnewannouncement(Request $request)
    {
        $ann = new announce();
        $ann->title = $request->title;
        $ann->messages = $request->messages;
        $ann->status = 'Active';
        $ann->save();

        return back()->with('selamat', 'Berhasil menambah pengumuman');
    }
    public function editannounce($id, Request $request)
    {
        $ann = announce::find($id);
        $ann->title = $request->title;
        $ann->messages = $request->messages;
        $ann->status = $request->status;
        $ann->save();
        return back()->with('selamat', 'Pengumuman berhasil diupdate');
    }
    // END

    // Partner Section
    public function showpartner()
    {
        $partner = partner::orderBy('created_at', 'DESC')->get();
        return view('dashboard.partner.show', ['partner' => $partner]);
    }
    public function prosesaddpartner(Request $request)
    {
        $partner = new partner();
        $partner->title = $request->title;
        if (!$request->hasFile('image')) {
            $partner->save();
        } else {
            $lamp = $request->file('image');
            $filename = time() . '.' . $lamp->getClientOriginalExtension();
            $request->file('image')->move('media/partner/', $filename);
            $partner->image = $filename;
            $partner->save();
        }
        return back()->with('selamat', 'Partner kamu berhasil ditambahkan!');
    }
    public function trashpartner($id)
    {
        $partner = partner::find($id);
        // dd($user);
        if ($partner) {
            if ($partner->delete()) {
                DB::statement('ALTER TABLE partners AUTO_INCREMENT = ' . (count(partner::all()) + 1) . ';');

                return back()->with('selamat', 'Data Partner dalam sistem ini berhasil dihapus.');
            }
        }
    }
    // End Section


}
