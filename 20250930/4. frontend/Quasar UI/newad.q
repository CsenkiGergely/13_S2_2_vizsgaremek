  <q-page>
    <div class="row justify-center q-pa-md">
      <div class="col-12 col-sm-8 col-md-6 col-lg-4">
        <q-form class="q-gutter-md text-center" @submit="onSubmit">
          <h5>Új hirdetés elküldése</h5>
          <q-select
            clearable
            dense
            emit-value
            label="Ingatlan kategóriája"
            map-options
            outlined
            :rules="[(v) => v != null || 'Kérem válasszon!']"
          />
          <q-input
            clearable
            label="Hírdetés dátuma"
            outlined
            type="date"
          />
          <q-input
            dense
            label="Leírás"
            outlined
            :rules="[(v) => (v != null && v != '') || 'Kérem töltse ki a mezőt!']"
            type="textarea"
          />
          <div class="text-left">
            <q-checkbox label="Tehermentes ingatlan"/>
          </div>

          <q-input
            dense
            label="Fénykép az ingatlanról"
            outlined
            :rules="[(v) => (v != null && v != '') || 'Kérem töltse ki a mezőt!']"
            type="url"
          />
          <q-btn class="q-px-md" color="green" label="Küldés" no-caps type="submit" />
        </q-form>
      </div>
    </div>
  </q-page>