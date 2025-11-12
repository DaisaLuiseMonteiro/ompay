<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'montant' => 'required|numeric|min:0',
            'type_transaction' => 'required|in:paiement,virement,retrait,paiement_marchand',
            'destinataire_id' => 'nullable|required_if:type_transaction,virement|exists:clients,id',
            'marchand_id' => 'nullable|required_if:type_transaction,paiement_marchand|exists:marchands,id',
            'description' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'L\'ID du client est requis',
            'montant.required' => 'Le montant est requis',
            'montant.numeric' => 'Le montant doit être un nombre',
            'type_transaction.in' => 'Type de transaction invalide',
            'destinataire_id.required_if' => 'L\'ID du destinataire est requis pour un virement',
            'marchand_id.required_if' => 'L\'ID du marchand est requis pour un paiement marchand',
        ];
    }
}