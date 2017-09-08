<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Post;
use App\Tag;
use App\Category;
use Session;
use Purifier;
use Image;
use Storage;
use App\Events\PostCreatedEvent;

class PostController extends Controller
{

    public function __construct() {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::orderBy('id', 'desc')->paginate(10);
        return view('posts.index')->withPosts($posts);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();
        return view('posts.create')->withCategories($categories)->withTags($tags);
    }

    /**
     * Save the provided image into the storage folder
     * @method saveImage
     * @param  UploadedFile $image The uploaded image
     * @return String              The destination path
     */
    protected function saveImage(UploadedFile $image) {

        $folder = date('/Y/m/d/');
        Storage::makeDirectory($folder);

        // A better way to do this is to let Laravel generate a unique
        // filename and save it in the model the original filename.
        $filename = $image->getClientOriginalName();
        while (Storage::exists($folder . $filename)) {
            $filename = basename($image->getClientOriginalName(), '.' . $image->getClientOriginalExtension()) . '-' . rand(1, 1000) . '.' . $image->getClientOriginalExtension();
        }

        $location = $folder . $filename;

        // I kept the resize as I though it was the right thing to do
        // instead of showing a 10+ megapixel image in a blog post
        Image::make($image)->resize(800, 400)->save(storage_path('app/' . $location));

        return $location;

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validate the data
        $this->validate($request, array(
                'title'         => 'required|max:255',
                'slug'          => 'required|alpha_dash|min:5|max:255|unique:posts,slug',
                'category_id'   => 'required|integer',
                'body'          => 'required',
                'featured_img'  => 'required|file'
            ));

        // store in the database
        $post = new Post;

        $post->title = $request->title;
        $post->slug = $request->slug;
        $post->category_id = $request->category_id;
        $post->body = Purifier::clean($request->body);

        if ($request->hasFile('featured_img') && $request->file('featured_img')->isValid()) {
            $post->image = $this->saveImage($request->file('featured_img'));
        }

        $post->save();

        if ( $request->has('tags') && is_array($request->tags) )
            $post->tags()->sync($request->tags, false);

        Session::flash('success', 'The blog post was successfully save!');

        event(new PostCreatedEvent($post, $request->user()));

        return redirect()->route('posts.show', $post->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $post = Post::find($id);
        return view('posts.show')->withPost($post);
    }

    /**
     * Display the post image.
     *
     * @param  Post  $post     The selected post
     * @return \Illuminate\Http\Response
     */
    public function showImage(Post $post)
    {

        if (Storage::exists($post->image)) {
            return response()->file(storage_path('app/' . $post->image));
        }

        return redirect(asset('img/empty.png'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // find the post in the database and save as a var
        $post = Post::find($id);
        $categories = Category::all();
        $cats = array();
        foreach ($categories as $category) {
            $cats[$category->id] = $category->name;
        }

        $tags = Tag::all();
        $tags2 = array();
        foreach ($tags as $tag) {
            $tags2[$tag->id] = $tag->name;
        }
        // return the view and pass in the var we previously created
        return view('posts.edit')->withPost($post)->withCategories($cats)->withTags($tags2);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        // Validate the data
        $post = Post::find($id);

        if ($request->input('slug') == $post->slug) {
            $this->validate($request, array(
                'title' => 'required|max:255',
                'category_id' => 'required|integer',
                'body'  => 'required',
                'featured_img' => 'required|file',
            ));
        } else {
        $this->validate($request, array(
                'title' => 'required|max:255',
                'slug'  => 'required|alpha_dash|min:5|max:255|unique:posts,slug',
                'category_id' => 'required|integer',
                'body'  => 'required',
                'featured_img' => 'required|file',
            ));
        }

        // Save the data to the database
        $post = Post::find($id);

        $post->title = $request->input('title');
        $post->slug = $request->input('slug');
        $post->category_id = $request->input('category_id');
        $post->body = Purifier::clean($request->input('body'));

        if ($request->hasFile('featured_img') && $request->file('featured_img')->isValid()) {

            // Delete the old file if exists.
            if (Storage::exists($post->image)) {
                Storage::delete($post->image);
            }
    dd($request->file('featured_img'));
            $post->image = $this->saveImage($request->file('featured_img'));

        }

        $post->save();

        if (isset($request->tags)) {
            $post->tags()->sync($request->tags);
        } else {
            $post->tags()->sync(array());
        }


        // set flash data with success message
        Session::flash('success', 'This post was successfully saved.');

        // redirect with flash data to posts.show
        return redirect()->route('posts.show', $post->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $post = Post::find($id);
        $post->tags()->detach();

        $post->tags()->detach();

        $post->delete();

        Session::flash('success', 'The post was successfully deleted.');
        return redirect()->route('posts.index');
    }
}
