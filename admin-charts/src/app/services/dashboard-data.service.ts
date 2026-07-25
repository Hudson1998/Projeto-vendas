import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface PontoDado {
  label: string;
  valor: number;
}

export interface SatisfacaoResposta {
  media: number;
  totalAvaliacoes: number;
  distribuicao: PontoDado[];
}

export type Granularidade = 'dia' | 'mes' | 'ano';

@Injectable({ providedIn: 'root' })
export class DashboardDataService {
  private readonly base = '/admin/graficos';

  constructor(private http: HttpClient) {}

  acessos(granularidade: Granularidade): Observable<PontoDado[]> {
    return this.http.get<PontoDado[]>(`${this.base}/acessos`, { params: { granularidade } });
  }

  receita(granularidade: Granularidade): Observable<PontoDado[]> {
    return this.http.get<PontoDado[]>(`${this.base}/receita`, { params: { granularidade } });
  }

  volumeCompras(): Observable<PontoDado[]> {
    return this.http.get<PontoDado[]>(`${this.base}/volume-compras`);
  }

  satisfacao(): Observable<SatisfacaoResposta> {
    return this.http.get<SatisfacaoResposta>(`${this.base}/satisfacao`);
  }

  vendasPorCategoria(): Observable<PontoDado[]> {
    return this.http.get<PontoDado[]>(`${this.base}/vendas-categoria`);
  }
}
