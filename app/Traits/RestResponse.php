<?php

namespace App\Traits;

trait RestResponse
{
   protected function successResponse($data, $message = '', $pagination = null, $links = null, $code = 200)
   {
       $response = [
           'code' => $code,
           'success' => true,
           'data' => $data,
       ];


       if ($message) {
           $response['message'] = $message;
       }


       if ($pagination) {
           $response['pagination'] = $pagination;
       }


       if ($links) {
           $response['links'] = $links;
       }


       return response()->json($response, $code);
   }


   protected function errorResponse($message = 'Error', $code = 400, $details = [])
   {


       $errors = [
           'code' => $code,
           'message' => $message,
       ];


       if (!empty($details)) {
           $errors['details'] = $details;
       }


       return response()->json([
           'success' => false,
           'errors' => $errors,
       ], $code);
   }


   protected function paginatedData($paginator)
   {
       return [
           'currentPage' => $paginator->currentPage(),
           'totalPages' => $paginator->lastPage(),
           'perPage' => $paginator->perPage(),
           'totalItems' => $paginator->total(),
       ];
   }


   protected function getLinksData($paginator)
   {
       return [
           'first' => $paginator->url(1),
           'last' => $paginator->url($paginator->lastPage()),
           'prev' => $paginator->previousPageUrl(),
           'next' => $paginator->nextPageUrl(),   
       ];
   }
}
