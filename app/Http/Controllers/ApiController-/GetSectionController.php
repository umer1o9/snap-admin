<?php

namespace App\Http\Controllers;

use App\Models\GetSection;
use Illuminate\Http\Request;

class GetSectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_section($)
    {
        //
        $response = get_current_user();
        dd($response);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GetSection  $getSection
     * @return \Illuminate\Http\Response
     */
    public function show(GetSection $getSection)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GetSection  $getSection
     * @return \Illuminate\Http\Response
     */
    public function edit(GetSection $getSection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\GetSection  $getSection
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, GetSection $getSection)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GetSection  $getSection
     * @return \Illuminate\Http\Response
     */
    public function destroy(GetSection $getSection)
    {
        //
    }
}
